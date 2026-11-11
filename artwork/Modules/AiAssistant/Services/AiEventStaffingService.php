<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiEventStaffingData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiStaffingEventData;
use Artwork\Modules\AiAssistant\DTOs\AiStaffingPickData;
use Artwork\Modules\AiAssistant\DTOs\AiStaffingTotalsData;
use Artwork\Modules\AiAssistant\DTOs\AiStaffingUnfilledData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gewerk fuer einen Termin besetzen — Vorschlag (ai-scenarios-3 S3). Schreibt nichts.
 *
 * Offene Plaetze (`AiShiftPlanningService::openDemand()`) der Schichten des Gewerks am Termin werden
 * chronologisch gefuellt. Je Platz kommen die Personen aus
 * `AiShiftInsightsService::eligiblePeople()` in Frage (wenigste geplante Minuten in der Woche
 * zuerst). Eine Person bekommt einen Platz nur, wenn **alle** ihre bisherigen Picks plus dieser
 * Platz zusammen in **einer** Simulation (`AiShiftPlanningService::evaluate()`, Regeln hart) ohne
 * Konflikt bleiben — so entstehen keine Ueberschneidungen und keine Regelverstoesse ueber Picks
 * hinweg, und dieselbe Person landet nie zweimal in derselben Schicht.
 *
 * @phpstan-import-type Candidate from AiShiftPlanningService
 */
class AiEventStaffingService
{
    public const UNFILLED_NO_PEOPLE = 'no_eligible_people';

    public const UNFILLED_RULES = 'all_candidates_violate_rules';

    public const UNFILLED_LIMIT = 'limit';

    public function __construct(
        private readonly AiShiftPlanningService $planning,
        private readonly AiShiftInsightsService $insights,
        private readonly AiShiftHypothesis $hypothesis,
        private readonly AiPeopleService $people,
        private readonly CraftScopeService $craftScope,
    ) {
    }

    /**
     * @param list<array{type: string, id: int}>|null $onlyPeople
     */
    public function suggest(
        User $planner,
        int $eventId,
        int $craftId,
        ?int $qualificationId,
        ?array $onlyPeople,
        ?int $maxMinutesPerPersonWeek
    ): AiEventStaffingData {
        $event = Event::query()->visibleFor($planner)->find($eventId);
        abort_if($event === null, Response::HTTP_NOT_FOUND);
        $craft = Craft::query()->find($craftId);
        abort_if($craft === null, Response::HTTP_NOT_FOUND);
        $this->craftScope->assertCanPlan($planner, [$craftId]);

        $allowed = $onlyPeople === null ? null : array_map(
            static fn (array $person): string => $person['type'] . '#' . $person['id'],
            $onlyPeople
        );

        $shifts = Shift::query()
            ->visibleFor($planner)
            ->where('event_id', $event->id)
            ->where('craft_id', $craftId)
            ->with(['craft', 'event', 'room'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->orderBy('id')
            ->get();

        $open = $this->planning->openDemand($shifts->modelKeys());
        $names = ShiftQualification::query()->pluck('name', 'id')->all();
        $limits = ['max_minutes_per_week' => $maxMinutesPerPersonWeek];

        /** @var array<string, array{person: User|Freelancer|ServiceProvider, picks: list<Candidate>, planned: array<string, int>}> $state */
        $state = [];
        $picks = [];
        $unfilled = [];
        $slots = 0;

        foreach ($shifts as $shift) {
            $interval = AiShiftPlanningService::intervalOf($shift);
            if ($interval === null) {
                continue;
            }

            $demand = $open[$shift->id] ?? [];
            ksort($demand);

            foreach ($demand as $qualification => $missing) {
                if ($missing < 1 || ($qualificationId !== null && $qualification !== $qualificationId)) {
                    continue;
                }

                $slots += $missing;
                $people = $this->insights->eligiblePeople($shift, (int) $qualification)
                    ->filter(static fn (Model $person): bool => $allowed === null
                        || in_array(AiPeopleService::typeOf($person) . '#' . $person->getKey(), $allowed, true))
                    ->values();
                $filled = 0;
                $reasons = [];

                while ($filled < $missing) {
                    $candidate = [
                        'shift' => $shift,
                        'shift_id' => (int) $shift->id,
                        'new_shift_index' => null,
                        'qualification_id' => (int) $qualification,
                        'start' => $interval[0],
                        'end' => $interval[1],
                        'pre_reasons' => [],
                    ];
                    $chosen = null;

                    foreach ($this->ordered($people, $state, $interval[0]) as $person) {
                        $key = AiPeopleService::typeOf($person) . '#' . $person->getKey();
                        $state[$key] ??= [
                            'person' => $person,
                            'picks' => [],
                            'planned' => $this->people->plannedMinutesPerDay(
                                $person,
                                CarbonImmutable::parse($interval[0]->toDateString()),
                                CarbonImmutable::parse($interval[1]->toDateString())
                            ),
                        ];

                        if (in_array((int) $shift->id, array_column($state[$key]['picks'], 'shift_id'), true)) {
                            continue;
                        }

                        $reason = $this->tryPick($state[$key], $candidate, $limits);
                        if ($reason === null) {
                            $chosen = $key;
                            break;
                        }

                        $reasons[] = $reason;
                    }

                    if ($chosen === null) {
                        break;
                    }

                    $state[$chosen]['picks'][] = $candidate;
                    $picks[] = new AiStaffingPickData(
                        shift_id: (int) $shift->id,
                        shift_qualification_id: (int) $qualification,
                        qualification: new AiRefData(
                            (int) $qualification,
                            AiText::truncate($names[$qualification] ?? null)
                        ),
                        start: $this->iso($interval[0]),
                        end: $this->iso($interval[1]),
                        person: $this->people->personData($state[$chosen]['person']),
                    );
                    $filled++;
                }

                if ($filled < $missing) {
                    $unfilled[] = new AiStaffingUnfilledData(
                        shift_id: (int) $shift->id,
                        qualification: new AiRefData(
                            (int) $qualification,
                            AiText::truncate($names[$qualification] ?? null)
                        ),
                        missing: $missing - $filled,
                        reason_code: $this->unfilledReason($people->count(), $reasons),
                    );
                }
            }
        }

        return new AiEventStaffingData(
            event: new AiStaffingEventData(
                id: (int) $event->id,
                name: AiText::truncate($event->eventName ?: $event->name),
                start: $event->start_time === null ? null : $this->iso($event->start_time),
                end: $event->end_time === null ? null : $this->iso($event->end_time),
            ),
            craft: new AiRefData((int) $craft->id, AiText::truncate($craft->name)),
            picks: $picks,
            unfilled: $unfilled,
            totals: new AiStaffingTotalsData($slots, count($picks), $slots - count($picks)),
        );
    }

    /**
     * Alle bisherigen Picks der Person plus der neue Platz in einer Simulation; `null` = passt.
     *
     * @param array{person: User|Freelancer|ServiceProvider, picks: list<Candidate>, planned: array<string, int>} $entry
     * @param Candidate $candidate
     * @param array{max_minutes_per_week: int|null} $limits
     */
    public function tryPick(array $entry, array $candidate, array $limits): ?string
    {
        $candidates = array_merge($entry['picks'], [$candidate]);
        usort(
            $candidates,
            static fn (array $a, array $b): int => [$a['start']->getTimestamp(), $a['shift']->id]
                <=> [$b['start']->getTimestamp(), $b['shift']->id]
        );

        /** @var list<array{accepted: bool, reasons: list<array{0: string, 1: string}>}> $evaluations */
        $evaluations = $this->hypothesis->simulate(
            fn (): array => $this->planning
                ->evaluate($entry['person'], $candidates, $entry['planned'], true, $limits)[0]
        );

        foreach ($evaluations as $evaluation) {
            if (!$evaluation['accepted']) {
                return $evaluation['reasons'][0][0];
            }
        }

        return null;
    }

    /**
     * Wenigste geplante Minuten in der Woche des Platzes zuerst (bereits Geplantes plus Picks).
     *
     * @param \Illuminate\Support\Collection<int, Model> $people
     * @param array<string, array{person: Model, picks: list<array<string, mixed>>, planned: array<string, int>}> $state
     * @return list<User|Freelancer|ServiceProvider>
     */
    public function ordered(\Illuminate\Support\Collection $people, array $state, Carbon $start): array
    {
        $week = AiPeopleService::weekKey(CarbonImmutable::parse($start->toDateString()));

        $minutes = [];
        foreach ($people as $person) {
            $key = AiPeopleService::typeOf($person) . '#' . $person->getKey();
            $capacity = $this->people->weekCapacity($person, CarbonImmutable::parse($start->toDateString()));
            $picked = 0;
            foreach ($state[$key]['picks'] ?? [] as $pick) {
                if (AiPeopleService::weekKey(CarbonImmutable::parse($pick['start']->toDateString())) === $week) {
                    $picked += (int) round($pick['start']->diffInMinutes($pick['end']));
                }
            }
            $minutes[$key] = $capacity->minutes_planned + $picked;
        }

        $sorted = $people->all();
        usort($sorted, static fn (Model $a, Model $b): int => [
            $minutes[AiPeopleService::typeOf($a) . '#' . $a->getKey()],
            mb_strtolower(AiPeopleService::nameOf($a)),
        ] <=> [
            $minutes[AiPeopleService::typeOf($b) . '#' . $b->getKey()],
            mb_strtolower(AiPeopleService::nameOf($b)),
        ]);

        /** @var list<User|Freelancer|ServiceProvider> $sorted */
        return $sorted;
    }

    /**
     * @param list<string> $reasons
     */
    public function unfilledReason(int $people, array $reasons): string
    {
        if ($people === 0 || $reasons === []) {
            return self::UNFILLED_NO_PEOPLE;
        }

        $limitOnly = $reasons !== [] && array_filter(
            $reasons,
            static fn (string $reason): bool => !str_starts_with($reason, 'limit_')
        ) === [];

        return $limitOnly ? self::UNFILLED_LIMIT : self::UNFILLED_RULES;
    }

    private function iso(\DateTimeInterface $moment): string
    {
        return CarbonImmutable::instance($moment)->setTimezone((string) config('app.timezone'))->toIso8601String();
    }
}
