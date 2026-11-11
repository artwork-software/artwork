<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiPersonData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiReplacementShiftData;
use Artwork\Modules\AiAssistant\DTOs\AiReplacementSuggestionData;
use Artwork\Modules\AiAssistant\DTOs\AiReplacementTotalsData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ausfall ersetzen — Vorschlag (ai-scenarios-4 S9). Schreibt nichts.
 *
 * Alle Schichten der ausfallenden Person im Zeitraum (sichtbar, im Gewerks-Scope des Planers,
 * optional ein Gewerk). Je Schicht und Funktion kommen die Personen aus
 * `AiShiftInsightsService::eligiblePeople()` in Frage — die ausfallende Person steht selbst in der
 * Schicht und ist damit ausgeschlossen. Ausgewaehlt wird mit derselben Logik wie `staff-event`
 * (`AiEventStaffingService::ordered()`/`tryPick()`): je Ersatzperson eine Simulation ueber alle
 * ihre Picks, damit niemand zwei ueberlappende Ersatzschichten oder einen Regelverstoss bekommt.
 * `alternatives` sind bis zu drei weitere Personen, die die Schicht (mit ihren bisherigen Picks)
 * ebenfalls ohne Konflikt uebernehmen koennten — sie werden nicht fest eingeplant.
 *
 * @phpstan-import-type Candidate from AiShiftPlanningService
 */
class AiReplacementService
{
    public const MAX_ALTERNATIVES = 3;

    public function __construct(
        private readonly AiEventStaffingService $staffing,
        private readonly AiShiftInsightsService $insights,
        private readonly AiPeopleService $people,
        private readonly CraftScopeService $craftScope,
    ) {
    }

    public function suggest(
        User $planner,
        User|Freelancer|ServiceProvider $absent,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $craftId
    ): AiReplacementSuggestionData {
        $names = ShiftQualification::query()->pluck('name', 'id')->all();
        /** @var array<string, array{person: User|Freelancer|ServiceProvider, picks: list<Candidate>, planned: array<string, int>}> $state */
        $state = [];
        $rows = [];
        $covered = 0;

        foreach ($this->assignments($planner, $absent, $from, $to, $craftId) as [$shift, $pivot]) {
            $interval = AiShiftPlanningService::intervalOf($shift);
            if ($interval === null) {
                continue;
            }

            $qualification = (int) $pivot->shift_qualification_id;
            $candidate = [
                'shift' => $shift,
                'shift_id' => (int) $shift->id,
                'new_shift_index' => null,
                'qualification_id' => $qualification,
                'start' => $interval[0],
                'end' => $interval[1],
                'pre_reasons' => [],
            ];
            $people = $this->insights->eligiblePeople($shift, $qualification);
            $limit = (int) config('ai.list_limit');
            $replacement = null;
            $alternatives = [];
            $reasons = [];
            $tried = 0;

            foreach ($this->staffing->ordered($people, $state, $interval[0]) as $person) {
                if ($tried++ >= $limit || count($alternatives) >= self::MAX_ALTERNATIVES) {
                    break;
                }

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

                $reason = $this->staffing->tryPick($state[$key], $candidate, ['max_minutes_per_week' => null]);
                if ($reason !== null) {
                    $reasons[] = $reason;
                    continue;
                }

                if ($replacement === null) {
                    $replacement = $key;
                    continue;
                }

                $alternatives[] = $this->people->personData($person);
            }

            if ($replacement !== null) {
                $state[$replacement]['picks'][] = $candidate;
                $covered++;
            }

            $rows[] = new AiReplacementShiftData(
                shift_id: (int) $shift->id,
                shift_qualification_id: $qualification,
                qualification: new AiRefData($qualification, AiText::truncate($names[$qualification] ?? null)),
                start: $this->iso($interval[0]),
                end: $this->iso($interval[1]),
                event: AiRefData::make(
                    $shift->event_id === null ? null : (int) $shift->event_id,
                    $shift->event === null ? null : ($shift->event->eventName ?: $shift->event->name)
                ),
                craft: new AiRefData((int) $shift->craft_id, AiText::truncate($shift->craft?->name)),
                replacement: $replacement === null ? null : $this->personData($state[$replacement]['person']),
                alternatives: $alternatives,
                reason_code: $replacement === null
                    ? $this->staffing->unfilledReason($people->count(), $reasons)
                    : null,
            );
        }

        return new AiReplacementSuggestionData(
            person: $this->people->personData($absent),
            shifts: $rows,
            totals: new AiReplacementTotalsData(count($rows), $covered, count($rows) - $covered),
        );
    }

    /**
     * Zuweisungen der Person an sichtbaren, planbaren Schichten mit Beginn im Zeitraum,
     * chronologisch.
     *
     * @return list<array{0: Shift, 1: ShiftWorker}>
     */
    public function assignments(
        User $planner,
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $craftId
    ): array {
        $plannable = $this->craftScope->plannableCraftIdsFor($planner);
        $pivots = ShiftWorker::query()
            ->where('employable_type', $person->getMorphClass())
            ->where('employable_id', $person->getKey())
            ->get()
            ->keyBy('shift_id');

        $shifts = Shift::query()
            ->visibleFor($planner)
            ->whereIn('id', $pivots->keys()->all())
            ->whereDate('start_date', '>=', $from->toDateString())
            ->whereDate('start_date', '<=', $to->toDateString())
            ->when($plannable !== null, static fn (Builder $query) => $query->whereIn('craft_id', $plannable))
            ->when($craftId !== null, static fn (Builder $query) => $query->where('craft_id', $craftId))
            ->with(['craft', 'event', 'room'])
            ->orderBy('start_date')
            ->orderBy('start')
            ->orderBy('id')
            ->get();

        return $shifts->map(static fn (Shift $shift): array => [$shift, $pivots[$shift->id]])->values()->all();
    }

    private function personData(Model $person): AiPersonData
    {
        return $this->people->personData($person);
    }

    private function iso(\DateTimeInterface $moment): string
    {
        return CarbonImmutable::instance($moment)->setTimezone((string) config('app.timezone'))->toIso8601String();
    }
}
