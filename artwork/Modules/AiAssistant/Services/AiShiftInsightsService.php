<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiCandidateShiftData;
use Artwork\Modules\AiAssistant\DTOs\AiCraftCapacityData;
use Artwork\Modules\AiAssistant\DTOs\AiCraftCapacityEntryData;
use Artwork\Modules\AiAssistant\DTOs\AiCraftData;
use Artwork\Modules\AiAssistant\DTOs\AiCraftListData;
use Artwork\Modules\AiAssistant\DTOs\AiOpenDemandData;
use Artwork\Modules\AiAssistant\DTOs\AiOpenShiftData;
use Artwork\Modules\AiAssistant\DTOs\AiOpenShiftListData;
use Artwork\Modules\AiAssistant\DTOs\AiOpenShiftsSummaryData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRejectedCandidateData;
use Artwork\Modules\AiAssistant\DTOs\AiRuleRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRuleViolationData;
use Artwork\Modules\AiAssistant\DTOs\AiRuleViolationListData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftCandidateData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftCandidateListData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lesende Planungs-Endpunkte aus Erweiterung 2 (ai-shift-planning): offene Schichten,
 * Regelverstoesse, Kandidat:innen fuer eine Schicht, Gewerke und Gewerks-Kapazitaet.
 *
 * Alle setzen „Schichten planen" voraus (Controller) und schraenken auf die Gewerke ein, die die
 * fragende Person planen darf (`CraftScopeService`). Kandidat:innen werden mit derselben
 * Bewertung wie der Vorschlag geprueft (`AiShiftPlanningService::evaluate()`), je Person in einer
 * eigenen, zurueckgerollten Simulation.
 */
class AiShiftInsightsService
{
    public function __construct(
        private readonly AiShiftPlanningService $planning,
        private readonly AiShiftHypothesis $hypothesis,
        private readonly AiPeopleService $people,
        private readonly CraftScopeService $craftScope,
    ) {
    }

    public function openShifts(
        User $planner,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $craftId,
        ?int $limit
    ): AiOpenShiftListData {
        $limit = $this->limit($limit);
        $shifts = $this->plannableShifts($planner, $from, $to, $craftId)->with(['craft', 'event', 'room'])->get();
        $open = $this->planning->openDemand($shifts->modelKeys());
        $names = $this->qualificationNames();

        $entries = [];
        $missingTotal = 0;

        foreach ($shifts as $shift) {
            $demand = [];

            foreach ($open[$shift->id] ?? [] as $qualificationId => $missing) {
                if ($missing > 0) {
                    $demand[] = new AiOpenDemandData(
                        new AiRefData($qualificationId, AiText::truncate($names[$qualificationId] ?? null)),
                        $missing
                    );
                    $missingTotal += $missing;
                }
            }

            if ($demand !== []) {
                $entries[] = $this->openShiftEntry($shift, $demand);
            }
        }

        return new AiOpenShiftListData(
            data: array_slice($entries, 0, $limit),
            total: count($entries),
            limit: $limit,
            truncated: count($entries) > $limit,
            summary: new AiOpenShiftsSummaryData(count($entries), $missingTotal),
        );
    }

    public function violations(
        User $planner,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $craftId,
        string $status,
        ?int $limit
    ): AiRuleViolationListData {
        $limit = $this->limit($limit);
        $plannable = $this->craftScope->plannableCraftIdsFor($planner);

        $query = ShiftRuleViolation::query()
            ->whereBetween('violation_date', [$from->toDateString(), $to->toDateString()])
            ->when($status !== 'all', static fn (Builder $builder) => $builder->where('status', $status))
            ->when($plannable !== null, fn (Builder $builder) => $this->inCrafts($builder, $plannable))
            ->when($craftId !== null, fn (Builder $builder) => $this->inCrafts($builder, [(int) $craftId]));

        $total = (clone $query)->count();
        $violations = $query
            ->with(['shiftRule', 'user', 'shift.craft'])
            ->orderBy('violation_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return new AiRuleViolationListData(
            data: $violations->map(fn (ShiftRuleViolation $violation): AiRuleViolationData => new AiRuleViolationData(
                id: (int) $violation->id,
                person: new AiPersonRefData(
                    AiPeopleService::TYPE_USER,
                    (int) $violation->user_id,
                    (string) AiText::truncate(
                        $violation->user === null ? '' : AiPeopleService::nameOf($violation->user)
                    ),
                ),
                rule: new AiRuleRefData(
                    $violation->shiftRule?->trigger_type,
                    AiText::truncate($violation->getDisplayName()),
                ),
                date: $violation->violation_date?->toDateString(),
                severity: $violation->severity,
                status: $violation->status,
                detail: AiText::truncate($this->detail($violation)),
                shift: AiRefData::make(
                    $violation->shift_id === null ? null : (int) $violation->shift_id,
                    $violation->shift?->craft?->name
                ),
            ))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    public function candidates(User $planner, Shift $shift, ?int $qualificationId): AiShiftCandidateListData
    {
        $this->craftScope->assertCanPlanShifts($planner, [$shift]);
        $shift->loadMissing(['craft', 'event', 'room']);

        $open = $this->planning->openDemand([(int) $shift->id])[$shift->id] ?? [];
        $qualificationId ??= $this->firstOpenQualification($open);
        abort_if(
            $qualificationId === null || !array_key_exists($qualificationId, $open),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Die Schicht hat keinen Platz in dieser Funktion.'
        );

        $interval = AiShiftPlanningService::intervalOf($shift);
        abort_if($interval === null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Die Schicht hat keine Zeiten.');

        $limit = (int) config('ai.list_limit');
        $people = $this->eligiblePeople($shift, $qualificationId);
        $data = [];
        $rejected = [];

        foreach ($people->take($limit) as $person) {
            $candidate = [
                'shift' => $shift,
                'shift_id' => (int) $shift->id,
                'new_shift_index' => null,
                'qualification_id' => $qualificationId,
                'start' => $interval[0],
                'end' => $interval[1],
                'pre_reasons' => [],
            ];

            /** @var array{accepted: bool, reasons: list<array{0: string, 1: string}>} $evaluation */
            $evaluation = $this->hypothesis->simulate(
                fn (): array => $this->planning->evaluate($person, [$candidate], [], true)[0][0]
            );

            if (!$evaluation['accepted']) {
                $rejected[] = new AiRejectedCandidateData(
                    $this->personRef($person),
                    $evaluation['reasons'][0][0],
                    (string) AiText::truncate($evaluation['reasons'][0][1]),
                );
                continue;
            }

            $capacity = $this->people->weekCapacity($person, CarbonImmutable::parse($interval[0]->toDateString()));
            $data[] = new AiShiftCandidateData(
                $this->people->personData($person),
                $capacity->minutes_planned,
                $capacity->minutes_remaining,
            );
        }

        $names = $this->qualificationNames();

        return new AiShiftCandidateListData(
            shift: new AiCandidateShiftData(
                id: (int) $shift->id,
                craft: AiRefData::make($shift->craft_id, $shift->craft?->name),
                start: $this->iso($interval[0]),
                end: $this->iso($interval[1]),
                event: AiRefData::make(
                    $shift->event_id,
                    $shift->event === null ? null : ($shift->event->eventName ?: $shift->event->name)
                ),
                room: $this->roomOf($shift),
                qualification: new AiRefData($qualificationId, AiText::truncate($names[$qualificationId] ?? null)),
                open: $open[$qualificationId],
            ),
            data: $data,
            rejected: $rejected,
            truncated: $people->count() > $limit,
        );
    }

    public function crafts(User $planner, ?string $search, ?int $limit): AiCraftListData
    {
        $limit = $this->limit($limit);
        $plannable = $this->craftScope->plannableCraftIdsFor($planner);

        $query = Craft::query()->when(
            $search !== null,
            fn (Builder $builder) => $builder->where(function (Builder $sub) use ($search): void {
                $pattern = $this->contains((string) $search);
                $sub->where('name', 'like', $pattern)->orWhere('abbreviation', 'like', $pattern);
            })
        );

        $total = (clone $query)->count();
        $crafts = $query->orderBy('name')->limit($limit)->get();

        return new AiCraftListData(
            data: $crafts->map(static fn (Craft $craft): AiCraftData => new AiCraftData(
                id: (int) $craft->id,
                name: AiText::truncate($craft->name),
                abbreviation: AiText::truncate($craft->abbreviation),
                plannable: $plannable === null || in_array((int) $craft->id, $plannable, true),
            ))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    public function craftCapacity(User $planner, Craft $craft, CarbonImmutable $weekStart): AiCraftCapacityData
    {
        $this->craftScope->assertCanPlan($planner, [(int) $craft->id]);
        $weekEnd = $weekStart->addDays(6);

        $entries = [];
        foreach ([User::class, Freelancer::class, ServiceProvider::class] as $class) {
            $members = $class::query()
                ->where('can_work_shifts', true)
                ->whereHas('assignedCrafts', static fn (Builder $query) => $query->where('crafts.id', $craft->id))
                ->with(['assignedCrafts', 'shiftQualifications'])
                ->get();

            foreach ($members as $person) {
                $capacity = $this->people->weekCapacity($person, $weekStart);
                $entries[] = new AiCraftCapacityEntryData(
                    person: $this->people->personData($person),
                    minutes_planned: $capacity->minutes_planned,
                    minutes_max: $capacity->minutes_max,
                    minutes_remaining: $capacity->minutes_remaining,
                    limited_by: $capacity->limited_by,
                    absences: $this->people->absences($person, $weekStart, $weekEnd),
                );
            }
        }

        usort($entries, static fn (AiCraftCapacityEntryData $a, AiCraftCapacityEntryData $b): int => [
            $a->minutes_remaining === null ? 1 : 0,
            -($a->minutes_remaining ?? 0),
            mb_strtolower($a->person->name),
        ] <=> [
            $b->minutes_remaining === null ? 1 : 0,
            -($b->minutes_remaining ?? 0),
            mb_strtolower($b->person->name),
        ]);

        return new AiCraftCapacityData(
            craft: new AiRefData((int) $craft->id, AiText::truncate($craft->name)),
            week: AiPeopleService::weekKey($weekStart),
            data: $entries,
        );
    }

    /**
     * @return Builder<Shift>
     */
    private function plannableShifts(User $planner, CarbonImmutable $from, CarbonImmutable $to, ?int $craftId): Builder
    {
        $plannable = $this->craftScope->plannableCraftIdsFor($planner);

        return Shift::query()
            ->visibleFor($planner)
            ->whereDate('start_date', '>=', $from->toDateString())
            ->whereDate('start_date', '<=', $to->toDateString())
            ->when($plannable !== null, static fn (Builder $query) => $query->whereIn('craft_id', $plannable))
            ->when($craftId !== null, static fn (Builder $query) => $query->where('craft_id', $craftId))
            ->orderBy('start_date')
            ->orderBy('start')
            ->orderBy('id');
    }

    /**
     * Verstoss gehoert zu einem der Gewerke: ueber die Schicht, oder — ohne Schicht — ueber die
     * Gewerke der Person.
     *
     * @param Builder<ShiftRuleViolation> $builder
     * @param list<int> $craftIds
     */
    private function inCrafts(Builder $builder, array $craftIds): void
    {
        $builder->where(static function (Builder $sub) use ($craftIds): void {
            $sub->whereHas('shift', static fn (Builder $shift) => $shift->whereIn('craft_id', $craftIds))
                ->orWhere(static function (Builder $withoutShift) use ($craftIds): void {
                    $withoutShift->whereNull('shift_id')->whereHas(
                        'user.assignedCrafts',
                        static fn (Builder $crafts) => $crafts->whereIn('crafts.id', $craftIds)
                    );
                });
        });
    }

    /**
     * Personen im Gewerk der Schicht (oder in einem universell einsetzbaren Gewerk) mit der Funktion,
     * nicht schon eingeteilt — Regel aus `ShiftReplacementService::candidateQuery()`, zusaetzlich
     * Dienstleister. Sortiert nach Stundenkonto (niedrigstes zuerst, unbekannt zuletzt), dann Name.
     *
     * @return Collection<int, User|Freelancer|ServiceProvider>
     */
    public function eligiblePeople(Shift $shift, int $qualificationId): Collection
    {
        $universal = Craft::query()->where('universally_applicable', true)->pluck('id')
            ->map(static fn ($id): int => (int) $id)->all();
        $allowed = array_values(array_unique(array_merge([(int) $shift->craft_id], $universal)));

        $people = collect();
        foreach ([User::class, Freelancer::class, ServiceProvider::class] as $class) {
            $people = $people->concat($class::query()
                ->where('can_work_shifts', true)
                ->whereHas('assignedCrafts', static fn (Builder $query) => $query->whereIn('crafts.id', $allowed))
                ->whereHas('shiftQualifications', static function (
                    Builder $query
                ) use (
                    $qualificationId,
                    $allowed
                ): void {
                    $query->where('shift_qualifications.id', $qualificationId)
                        ->where(static function (Builder $pivot) use ($allowed): void {
                            $pivot->whereIn('shift_qualifiables.craft_id', $allowed)
                                ->orWhereNull('shift_qualifiables.craft_id');
                        });
                })
                ->whereNotExists(static function ($query) use ($shift, $class): void {
                    $query->select(DB::raw(1))
                        ->from('shift_workers')
                        ->where('shift_workers.shift_id', $shift->id)
                        ->where('shift_workers.employable_type', $class)
                        ->whereColumn('shift_workers.employable_id', (new $class())->getTable() . '.id')
                        ->whereNull('shift_workers.deleted_at');
                })
                ->with(['assignedCrafts', 'shiftQualifications'])
                ->get());
        }

        $useBalance = WorkTimeAccounting::isEnabled();

        return $people->sortBy(static fn (Model $person): array => [
            $useBalance && $person instanceof User ? 0 : 1,
            $useBalance && $person instanceof User ? (int) ($person->work_time_balance ?? 0) : 0,
            mb_strtolower(AiPeopleService::nameOf($person)),
        ])->values();
    }

    /**
     * @param array<int, int> $open
     */
    private function firstOpenQualification(array $open): ?int
    {
        ksort($open);

        foreach ($open as $qualificationId => $missing) {
            if ($missing > 0) {
                return (int) $qualificationId;
            }
        }

        return array_key_first($open);
    }

    /**
     * @param list<AiOpenDemandData> $demand
     */
    private function openShiftEntry(Shift $shift, array $demand): AiOpenShiftData
    {
        $interval = AiShiftPlanningService::intervalOf($shift);

        return new AiOpenShiftData(
            id: (int) $shift->id,
            craft: AiRefData::make($shift->craft_id, $shift->craft?->name),
            start: $this->iso($interval[0] ?? null),
            end: $this->iso($interval[1] ?? null),
            event: AiRefData::make(
                $shift->event_id,
                $shift->event === null ? null : ($shift->event->eventName ?: $shift->event->name)
            ),
            room: $this->roomOf($shift),
            open: $demand,
        );
    }

    private function roomOf(Shift $shift): ?AiRefData
    {
        $roomId = $shift->room_id ?? $shift->event?->room_id;

        return AiRefData::make(
            $roomId === null ? null : (int) $roomId,
            $shift->room?->name ?? $shift->event?->room?->name
        );
    }

    private function personRef(Model $person): AiPersonRefData
    {
        return new AiPersonRefData(
            AiPeopleService::typeOf($person),
            (int) $person->getKey(),
            (string) AiText::truncate(AiPeopleService::nameOf($person)),
        );
    }

    /**
     * Beschreibung der Regel plus die Messwerte des Verstosses (z. B. `planned_hours: 12`).
     */
    private function detail(ShiftRuleViolation $violation): string
    {
        $values = [];
        foreach ((array) ($violation->violation_data ?? []) as $key => $value) {
            if (is_scalar($value)) {
                $values[] = $key . ': ' . $value;
            }
        }

        return trim($violation->getViolationMessage() . ($values === [] ? '' : ' (' . implode(', ', $values) . ')'));
    }

    /**
     * @return array<int, string>
     */
    private function qualificationNames(): array
    {
        /** @var array<int, string> $names */
        $names = ShiftQualification::query()->pluck('name', 'id')
            ->map(static fn (mixed $name): string => (string) $name)
            ->all();

        return $names;
    }

    private function iso(?Carbon $moment): ?string
    {
        return $moment?->copy()->setTimezone((string) config('app.timezone'))->toIso8601String();
    }

    private function contains(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.list_limit');

        if ($requested === null || $requested < 1) {
            return $max;
        }

        return min($requested, $max);
    }
}
