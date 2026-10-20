<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiMonthTotalData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftLimitsData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftPickData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftSkipData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftSuggestionData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftTotalsData;
use Artwork\Modules\AiAssistant\DTOs\AiWeekTotalData;
use Artwork\Modules\AiAssistant\Http\Requests\SuggestAiShiftPlanRequest;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\LegalBreakCalculator;
use Artwork\Modules\Shift\Services\ShiftAssignmentPreflightService;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regelkonformer Schichtvorschlag fuer eine Person (ai-shift-planning, Core 3) und die gemeinsame
 * Bewertung, die auch die Vorschau von `assign_shifts` benutzt.
 *
 * Gerechnet wird deterministisch: Kandidaten chronologisch, eine Schicht wird genommen, wenn
 * Vorabpruefung (Ueberschneidung, Urlaub, nicht verfuegbar), Grenzen (Woche, Monat, gesamt — mit
 * den bereits geplanten Minuten) und — nur bei Nutzer:innen mit Vertrag — die Vertragsregeln mit
 * allen bis dahin gewaehlten Schichten ohne neuen Verstoss bleiben. Alles laeuft in
 * `AiShiftHypothesis::simulate()` und wird zurueckgerollt.
 *
 * @phpstan-type Candidate array{
 *     shift: Shift, shift_id: int|null, new_shift_index: int|null, qualification_id: int,
 *     start: Carbon, end: Carbon, pre_reasons: list<array{0: string, 1: string}>
 * }
 * @phpstan-type Evaluation array{
 *     candidate: Candidate, accepted: bool, reasons: list<array{0: string, 1: string}>,
 *     minutes: int, per_day: array<string, int>
 * }
 */
class AiShiftPlanningService
{
    public const REASON_OVERLAP = 'overlap';

    public const REASON_VACATION = 'vacation';

    public const REASON_UNAVAILABLE = 'unavailable';

    public const REASON_LIMIT_WEEK = 'limit_week';

    public const REASON_LIMIT_MONTH = 'limit_month';

    public const REASON_LIMIT_TOTAL = 'limit_total';

    public const REASON_EXCLUDED = 'excluded';

    public const REASON_NOT_QUALIFIED = 'not_qualified';

    public const REASON_RULE_PREFIX = 'rule:';

    public function __construct(
        private readonly AiPeopleService $people,
        private readonly AiShiftHypothesis $hypothesis,
        private readonly ShiftAssignmentPreflightService $preflight,
        private readonly ShiftRuleService $shiftRuleService,
        private readonly WorkTimeCalculationService $workTime,
        private readonly CraftScopeService $craftScope,
    ) {
    }

    /**
     * @param array{
     *     max_minutes_per_week: int|null, max_minutes_per_month: int|null, max_minutes_total: int|null,
     *     fill: string, exclude_weeks: list<string>, exclude_days: list<string>, craft_id: int|null
     * } $options
     * @param list<array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * }> $newShifts
     */
    public function suggest(
        User $planner,
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $from,
        CarbonImmutable $to,
        array $options,
        array $newShifts
    ): AiShiftSuggestionData {
        abort_unless(
            (bool) $person->can_work_shifts,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Diese Person kann keine Schichten uebernehmen.'
        );

        // Grenzen gegen ganze Wochen/Monate: geplant wird ueber alle beruehrten Wochen und Monate
        // gerechnet, einen Tag Rand fuer Schichten, die ueber das Ende des Zeitraums laufen.
        $planned = $this->people->plannedMinutesPerDay($person, $from, $to->addDay());

        $contractRules = $person instanceof User
            && $this->shiftRuleService->getActiveRulesForUser($person)->isNotEmpty();

        [$evaluations, $dayMinutes] = $this->hypothesis->simulate(function () use (
            $planner,
            $person,
            $from,
            $to,
            $options,
            $newShifts,
            $planned
        ): array {
            $candidates = $this->newShiftCandidates($planner, $person, $newShifts);

            if ($options['fill'] !== SuggestAiShiftPlanRequest::FILL_NONE) {
                $candidates = array_merge(
                    $candidates,
                    $this->openShiftCandidates($planner, $person, $from, $to, $options['craft_id'])
                );
            }

            usort(
                $candidates,
                static fn (array $a, array $b): int => [$a['start']->getTimestamp(), $a['shift']->id]
                    <=> [$b['start']->getTimestamp(), $b['shift']->id]
            );

            return $this->evaluate($person, $candidates, $planned, true, $options, $from, $to);
        });

        return new AiShiftSuggestionData(
            person: $this->people->personData($person),
            picked: array_values(array_map(
                fn (array $evaluation): AiShiftPickData => $this->pickData($evaluation),
                array_filter($evaluations, static fn (array $evaluation): bool => $evaluation['accepted'])
            )),
            skipped: array_values(array_map(
                fn (array $evaluation): AiShiftSkipData => new AiShiftSkipData(
                    shift_id: $evaluation['candidate']['shift_id'],
                    new_shift_index: $evaluation['candidate']['new_shift_index'],
                    start: $this->iso($evaluation['candidate']['start']),
                    reason_code: $evaluation['reasons'][0][0],
                    reason: (string) AiText::truncate($evaluation['reasons'][0][1]),
                ),
                array_filter($evaluations, static fn (array $evaluation): bool => !$evaluation['accepted'])
            )),
            totals: $this->totals($from, $to, $planned, $dayMinutes),
            limits_applied: new AiShiftLimitsData(
                max_minutes_per_week: $options['max_minutes_per_week'],
                max_minutes_per_month: $options['max_minutes_per_month'],
                max_minutes_total: $options['max_minutes_total'],
                fill: $options['fill'],
                exclude_weeks: $options['exclude_weeks'],
                exclude_days: $options['exclude_days'],
                craft_id: $options['craft_id'],
                contract_rules: $contractRules,
            ),
        );
    }

    /**
     * Bewertet Kandidaten der Reihe nach. **Muss in `AiShiftHypothesis::simulate()` laufen**: jede
     * angenommene Schicht wird hypothetisch zugewiesen, damit Ueberschneidung, Grenzen und
     * Vertragsregeln der naechsten Schicht sie sehen.
     *
     * `$greedy = true` (Vorschlag): die erste Begruendung verwirft die Schicht, sie wird wieder
     * entfernt. `$greedy = false` (Vorschau einer Zuweisung): jede Schicht bleibt zugewiesen, alle
     * Begruendungen werden gesammelt.
     *
     * @param list<Candidate> $candidates
     * @param array<string, int> $planned bereits geplante Minuten je Tag
     * @param array{
     *     max_minutes_per_week: int|null, max_minutes_per_month: int|null, max_minutes_total: int|null,
     *     exclude_weeks: list<string>, exclude_days: list<string>
     * }|array{} $limits
     * @return array{0: list<Evaluation>, 1: array<string, int>}
     */
    public function evaluate(
        User|Freelancer|ServiceProvider $person,
        array $candidates,
        array $planned,
        bool $greedy,
        array $limits = [],
        ?CarbonImmutable $from = null,
        ?CarbonImmutable $to = null
    ): array {
        $user = $person instanceof User ? $person : null;
        $checkRules = $user !== null && $this->shiftRuleService->getActiveRulesForUser($user)->isNotEmpty();
        $dayMinutes = $planned;
        $evaluations = [];

        foreach ($candidates as $candidate) {
            $reasons = $candidate['pre_reasons'];
            $perDay = $this->candidateMinutesPerDay(
                $person,
                $candidate['shift'],
                $candidate['start'],
                $candidate['end']
            );
            $minutes = array_sum($perDay);

            if ($reasons === []) {
                $reasons = array_merge(
                    $this->exclusionReasons($candidate, $perDay, $limits),
                    $this->preflightReasons($candidate['shift'], $person),
                    $this->limitReasons($perDay, $dayMinutes, $limits, $from, $to),
                );
            }

            if ($greedy && $reasons !== []) {
                $evaluations[] = $this->evaluation($candidate, false, $reasons, $minutes, $perDay);
                continue;
            }

            [$ruleFrom, $ruleTo] = [
                Carbon::parse($candidate['start']->toDateString())->subDay(),
                Carbon::parse($candidate['end']->toDateString())->addDay(),
            ];
            $before = $checkRules ? $this->hypothesis->violations($user, $ruleFrom, $ruleTo) : [];
            $assignmentId = $this->hypothesis->insertAssignment(
                $person,
                $candidate['shift'],
                $candidate['qualification_id']
            );

            if ($checkRules) {
                $reasons = array_merge(
                    $reasons,
                    $this->ruleReasons($before, $this->hypothesis->violations($user, $ruleFrom, $ruleTo), $candidate)
                );
            }

            if ($greedy && $reasons !== []) {
                $this->hypothesis->removeAssignment($assignmentId);
                $evaluations[] = $this->evaluation($candidate, false, $reasons, $minutes, $perDay);
                continue;
            }

            foreach ($perDay as $day => $dayValue) {
                $dayMinutes[$day] = ($dayMinutes[$day] ?? 0) + $dayValue;
            }

            $evaluations[] = $this->evaluation($candidate, $reasons === [], $reasons, $minutes, $perDay);
        }

        return [$evaluations, $dayMinutes];
    }

    /**
     * Vorhandene Schichten im Zeitraum mit offenem Bedarf in einer Funktion der Person, in einem
     * Gewerk, das die planende Person planen darf und in dem die Person arbeiten kann.
     *
     * @return list<Candidate>
     */
    public function openShiftCandidates(
        User $planner,
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $craftId
    ): array {
        $access = $this->craftAccess($person);
        $plannable = $this->craftScope->plannableCraftIdsFor($planner);

        $shifts = Shift::query()
            ->visibleFor($planner)
            ->whereDate('start_date', '>=', $from->toDateString())
            ->whereDate('start_date', '<=', $to->toDateString())
            ->when($plannable !== null, static fn (Builder $query) => $query->whereIn('craft_id', $plannable))
            ->when($craftId !== null, static fn (Builder $query) => $query->where('craft_id', $craftId))
            ->when(
                !$access['universal'],
                static fn (Builder $query) => $query->whereIn('craft_id', $access['crafts'])
            )
            ->whereNotExists(static function ($query) use ($person): void {
                $query->select(DB::raw(1))
                    ->from('shift_workers')
                    ->whereColumn('shift_workers.shift_id', 'shifts.id')
                    ->where('shift_workers.employable_type', $person->getMorphClass())
                    ->where('shift_workers.employable_id', $person->getKey())
                    ->whereNull('shift_workers.deleted_at');
            })
            ->with(['craft', 'event', 'room'])
            ->get();

        $open = $this->openDemand($shifts->modelKeys());
        $candidates = [];

        foreach ($shifts as $shift) {
            $interval = self::intervalOf($shift);

            if ($interval === null) {
                continue;
            }

            $qualificationId = null;
            foreach ($this->eligibleQualificationIds($person, (int) $shift->craft_id, $access) as $id) {
                if (($open[$shift->id][$id] ?? 0) > 0) {
                    $qualificationId = $id;
                    break;
                }
            }

            if ($qualificationId === null) {
                continue;
            }

            $candidates[] = [
                'shift' => $shift,
                'shift_id' => (int) $shift->id,
                'new_shift_index' => null,
                'qualification_id' => $qualificationId,
                'start' => $interval[0],
                'end' => $interval[1],
                'pre_reasons' => [],
            ];
        }

        return $candidates;
    }

    /**
     * `new_shifts` als hypothetische Schichten (nur innerhalb von `simulate()` aufrufen).
     *
     * @param list<array{
     *     event_id: int, craft_id: int, shift_qualification_id: int, start: string, end: string,
     *     break_minutes: int|null
     * }> $newShifts
     * @return list<Candidate>
     */
    public function newShiftCandidates(User $planner, User|Freelancer|ServiceProvider $person, array $newShifts): array
    {
        $access = $this->craftAccess($person);
        $plannable = $this->craftScope->plannableCraftIdsFor($planner);
        $candidates = [];

        foreach ($newShifts as $index => $row) {
            $event = Event::query()->visibleFor($planner)->find($row['event_id']);
            abort_if($event === null, Response::HTTP_NOT_FOUND);

            [$start, $end] = self::newShiftInterval($row);
            $shift = $this->hypothesis->insertShift(
                (int) $event->id,
                $row['craft_id'],
                $row['shift_qualification_id'],
                $start,
                $end,
                self::breakMinutesFor($row),
                $event->room_id === null ? null : (int) $event->room_id,
                $event->project_id === null ? null : (int) $event->project_id,
            );

            $reasons = [];
            if ($plannable !== null && !in_array($row['craft_id'], $plannable, true)) {
                $reasons[] = [self::REASON_EXCLUDED, 'Dieses Gewerk darf die planende Person nicht planen.'];
            } elseif (
                !in_array(
                    $row['shift_qualification_id'],
                    $this->eligibleQualificationIds($person, $row['craft_id'], $access),
                    true
                ) || (!$access['universal'] && !in_array($row['craft_id'], $access['crafts'], true))
            ) {
                $reasons[] = [self::REASON_NOT_QUALIFIED, 'Die Person hat diese Funktion in diesem Gewerk nicht.'];
            }

            $candidates[] = [
                'shift' => $shift,
                'shift_id' => null,
                'new_shift_index' => (int) $index,
                'qualification_id' => $row['shift_qualification_id'],
                'start' => $start,
                'end' => $end,
                'pre_reasons' => $reasons,
            ];
        }

        return $candidates;
    }

    /**
     * Gewerke der Person und ob sie ueber ein universell einsetzbares Gewerk jede Schicht
     * uebernehmen kann (Regel aus `ShiftReplacementService::candidateQuery()`).
     *
     * @return array{crafts: list<int>, universal: bool, universal_ids: list<int>}
     */
    public function craftAccess(User|Freelancer|ServiceProvider $person): array
    {
        $crafts = $person->assignedCrafts()->pluck('crafts.id')->map(static fn ($id): int => (int) $id)->all();
        $universalIds = Craft::query()
            ->where('universally_applicable', true)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return [
            'crafts' => array_values($crafts),
            'universal' => array_intersect($crafts, $universalIds) !== [],
            'universal_ids' => array_values($universalIds),
        ];
    }

    /**
     * Funktionen der Person, die im Gewerk gelten (`shift_qualifiables.craft_id` leer, gleich dem
     * Gewerk oder ein universelles Gewerk), aufsteigend nach ID.
     *
     * @param array{crafts: list<int>, universal: bool, universal_ids: list<int>} $access
     * @return list<int>
     */
    public function eligibleQualificationIds(
        User|Freelancer|ServiceProvider $person,
        int $craftId,
        array $access
    ): array {
        $ids = [];

        foreach ($person->shiftQualifications()->get() as $qualification) {
            $pivotCraft = data_get($qualification, 'pivot.craft_id');

            if (
                $pivotCraft === null
                || (int) $pivotCraft === $craftId
                || in_array((int) $pivotCraft, $access['universal_ids'], true)
            ) {
                $ids[] = (int) $qualification->id;
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    /**
     * Offener regulaerer Bedarf je Schicht und Funktion: `value` minus regulaere Zuweisungen
     * (ueberbuchte zaehlen nicht — wie `ShiftsQualificationsService::getRegularWorkerCount()`).
     *
     * @param list<int> $shiftIds
     * @return array<int, array<int, int>>
     */
    public function openDemand(array $shiftIds): array
    {
        if ($shiftIds === []) {
            return [];
        }

        $staffed = [];
        foreach (
            DB::table('shift_workers')
                ->select(['shift_id', 'shift_qualification_id', DB::raw('COUNT(*) as staffed')])
                ->whereIn('shift_id', $shiftIds)
                ->whereNull('deleted_at')
                ->where('is_overbooked', false)
                ->groupBy('shift_id', 'shift_qualification_id')
                ->get() as $row
        ) {
            $staffed[(int) $row->shift_id][(int) $row->shift_qualification_id] = (int) $row->staffed;
        }

        $open = [];
        foreach (
            DB::table('shifts_qualifications')
                ->select(['shift_id', 'shift_qualification_id', 'value'])
                ->whereIn('shift_id', $shiftIds)
                ->whereNull('deleted_at')
                ->get() as $row
        ) {
            $shiftId = (int) $row->shift_id;
            $qualificationId = (int) $row->shift_qualification_id;
            $open[$shiftId][$qualificationId] = max(
                0,
                (int) $row->value - ($staffed[$shiftId][$qualificationId] ?? 0)
            );
        }

        return $open;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function intervalOf(Shift $shift): ?array
    {
        return ShiftAssignmentPreflightService::resolveInterval(
            $shift->start_date,
            $shift->start,
            $shift->end_date ?: $shift->start_date,
            $shift->end,
        );
    }

    /**
     * @param array{start: string, end: string} $row
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function newShiftInterval(array $row): array
    {
        return [
            Carbon::createFromFormat('Y-m-d\TH:i', $row['start'])->second(0),
            Carbon::createFromFormat('Y-m-d\TH:i', $row['end'])->second(0),
        ];
    }

    /**
     * Ohne Angabe die gesetzliche Mindestpause — wie beim Anlegen in der Oberflaeche
     * (`ShiftController::storeShiftWithoutEvent()`).
     *
     * @param array{start: string, end: string, break_minutes: int|null} $row
     */
    public static function breakMinutesFor(array $row): int
    {
        [$start, $end] = self::newShiftInterval($row);

        return LegalBreakCalculator::resolveBreakMinutes(
            $row['break_minutes'],
            $start->format('H:i'),
            $end->format('H:i')
        );
    }

    /**
     * Effektive Minuten der Schicht je Tag, berechnet von `shiftMinutesPerDay()` auf einer Kopie der
     * Person, an der nur diese eine Schicht haengt.
     *
     * @return array<string, int>
     */
    public function candidateMinutesPerDay(
        User|Freelancer|ServiceProvider $person,
        Shift $shift,
        Carbon $start,
        Carbon $end
    ): array {
        $copy = clone $shift;
        $copy->setRelation('pivot', new ShiftWorker());
        $worker = clone $person;
        $worker->setRelation('shifts', collect([$copy]));

        /** @var array<string, int> $minutes */
        $minutes = $this->workTime->shiftMinutesPerDay(
            $worker,
            Carbon::parse($start->toDateString()),
            Carbon::parse($end->toDateString())
        );

        return array_filter($minutes, static fn (int $value): bool => $value > 0);
    }

    /**
     * @param Candidate $candidate
     * @param array<string, int> $perDay
     * @param array<string, mixed> $limits
     * @return list<array{0: string, 1: string}>
     */
    private function exclusionReasons(array $candidate, array $perDay, array $limits): array
    {
        $days = array_unique(array_merge([$candidate['start']->toDateString()], array_keys($perDay)));
        $excludedDays = $limits['exclude_days'] ?? [];
        $excludedWeeks = $limits['exclude_weeks'] ?? [];

        foreach ($days as $day) {
            $date = CarbonImmutable::parse($day);

            if (in_array($day, $excludedDays, true)) {
                return [[self::REASON_EXCLUDED, "Der Tag {$date->format('d.m.Y')} ist ausgeschlossen."]];
            }

            if (in_array(AiPeopleService::weekKey($date), $excludedWeeks, true)) {
                $week = AiPeopleService::weekKey($date);

                return [[self::REASON_EXCLUDED, "Die Woche {$week} ist ausgeschlossen."]];
            }
        }

        return [];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function preflightReasons(Shift $shift, User|Freelancer|ServiceProvider $person): array
    {
        $reasons = [];

        foreach ($this->preflight->check($shift, $person)['conflicts'] as $conflict) {
            $code = match ($conflict['type']) {
                ShiftAssignmentPreflightService::TYPE_VACATION => self::REASON_VACATION,
                ShiftAssignmentPreflightService::TYPE_UNAVAILABLE => self::REASON_UNAVAILABLE,
                default => self::REASON_OVERLAP,
            };
            $reasons[] = [$code, trim($conflict['label'] . ': ' . $conflict['detail'])];
        }

        return $reasons;
    }

    /**
     * @param array<string, int> $perDay
     * @param array<string, int> $dayMinutes
     * @param array<string, mixed> $limits
     * @return list<array{0: string, 1: string}>
     */
    private function limitReasons(
        array $perDay,
        array $dayMinutes,
        array $limits,
        ?CarbonImmutable $from,
        ?CarbonImmutable $to
    ): array {
        $reasons = [];
        $checks = [
            [self::REASON_LIMIT_WEEK, $limits['max_minutes_per_week'] ?? null, AiPeopleService::weekKey(...), 'Woche'],
            [
                self::REASON_LIMIT_MONTH,
                $limits['max_minutes_per_month'] ?? null,
                AiPeopleService::monthKey(...),
                'Monat',
            ],
        ];

        foreach ($checks as [$code, $max, $keyOf, $label]) {
            if ($max === null) {
                continue;
            }

            $added = [];
            foreach ($perDay as $day => $minutes) {
                $key = $keyOf(CarbonImmutable::parse($day));
                $added[$key] = ($added[$key] ?? 0) + $minutes;
            }

            foreach ($added as $key => $minutes) {
                $already = $this->sumWhere(
                    $dayMinutes,
                    static fn (string $day): bool => $keyOf(CarbonImmutable::parse($day)) === $key
                );

                if ($already + $minutes > $max) {
                    $reasons[] = [$code, sprintf(
                        '%s %s: %d + %d Minuten ueberschreiten die Grenze von %d Minuten.',
                        $label,
                        $key,
                        $already,
                        $minutes,
                        $max
                    )];
                    break;
                }
            }
        }

        $maxTotal = $limits['max_minutes_total'] ?? null;
        if ($maxTotal !== null && $from !== null && $to !== null) {
            $already = $this->sumWhere(
                $dayMinutes,
                static fn (string $day): bool => $day >= $from->toDateString() && $day <= $to->toDateString()
            );
            $minutes = array_sum($perDay);

            if ($already + $minutes > $maxTotal) {
                $reasons[] = [self::REASON_LIMIT_TOTAL, sprintf(
                    'Gesamt: %d + %d Minuten ueberschreiten die Grenze von %d Minuten.',
                    $already,
                    $minutes,
                    $maxTotal
                )];
            }
        }

        return $reasons;
    }

    /**
     * Neue Verstoesse durch die Zuweisung: Schluessel (Regel, Tag), die vorher nicht da waren, oder
     * Verstoesse, die an genau dieser Schicht haengen.
     *
     * @param array<string, \Artwork\Modules\Shift\Models\ShiftRuleViolation> $before
     * @param array<string, \Artwork\Modules\Shift\Models\ShiftRuleViolation> $after
     * @param Candidate $candidate
     * @return list<array{0: string, 1: string}>
     */
    private function ruleReasons(array $before, array $after, array $candidate): array
    {
        $reasons = [];

        foreach ($after as $key => $violation) {
            if (array_key_exists($key, $before) && (int) $violation->shift_id !== (int) $candidate['shift']->id) {
                continue;
            }

            $rule = $violation->shiftRule;
            $reasons[] = [
                self::REASON_RULE_PREFIX . (string) $rule?->trigger_type,
                sprintf(
                    'Vertragsregel „%s" waere am %s verletzt.',
                    (string) ($rule?->name ?? $rule?->trigger_type),
                    $violation->violation_date?->format('d.m.Y')
                ),
            ];
        }

        return $reasons;
    }

    /**
     * @param Candidate $candidate
     * @param list<array{0: string, 1: string}> $reasons
     * @param array<string, int> $perDay
     * @return Evaluation
     */
    private function evaluation(array $candidate, bool $accepted, array $reasons, int $minutes, array $perDay): array
    {
        return [
            'candidate' => $candidate,
            'accepted' => $accepted,
            'reasons' => $reasons,
            'minutes' => $minutes,
            'per_day' => $perDay,
        ];
    }

    /**
     * @param Evaluation $evaluation
     */
    private function pickData(array $evaluation): AiShiftPickData
    {
        $candidate = $evaluation['candidate'];
        $shift = $candidate['shift'];
        $event = $shift->event;
        $roomId = $shift->room_id ?? $event?->room_id;

        return new AiShiftPickData(
            shift_id: $candidate['shift_id'],
            new_shift_index: $candidate['new_shift_index'],
            shift_qualification_id: $candidate['qualification_id'],
            start: (string) $this->iso($candidate['start']),
            end: (string) $this->iso($candidate['end']),
            minutes: $evaluation['minutes'],
            craft: new AiRefData((int) $shift->craft_id, AiText::truncate($shift->craft?->name)),
            event: AiRefData::make($event?->id, $event === null ? null : ($event->eventName ?: $event->name)),
            room: AiRefData::make(
                $roomId === null ? null : (int) $roomId,
                $shift->room?->name ?? $event?->room?->name
            ),
        );
    }

    /**
     * @param array<string, int> $planned
     * @param array<string, int> $after
     */
    private function totals(CarbonImmutable $from, CarbonImmutable $to, array $planned, array $after): AiShiftTotalsData
    {
        $weeks = [];
        foreach (AiPeopleService::weekKeysBetween($from, $to) as $week) {
            $match = static fn (string $day): bool => AiPeopleService::weekKey(CarbonImmutable::parse($day)) === $week;
            $weeks[] = new AiWeekTotalData($week, $this->sumWhere($planned, $match), $this->sumWhere($after, $match));
        }

        $months = [];
        foreach (AiPeopleService::monthKeysBetween($from, $to) as $month) {
            $match = static fn (string $day): bool
                => AiPeopleService::monthKey(CarbonImmutable::parse($day)) === $month;
            $months[] = new AiMonthTotalData(
                $month,
                $this->sumWhere($planned, $match),
                $this->sumWhere($after, $match)
            );
        }

        return new AiShiftTotalsData($weeks, $months);
    }

    /**
     * @param array<string, int> $minutesPerDay
     * @param callable(string): bool $match
     */
    private function sumWhere(array $minutesPerDay, callable $match): int
    {
        $sum = 0;

        foreach ($minutesPerDay as $day => $minutes) {
            if ($match((string) $day)) {
                $sum += $minutes;
            }
        }

        return $sum;
    }

    private function iso(?Carbon $moment): ?string
    {
        return $moment?->copy()->setTimezone((string) config('app.timezone'))->toIso8601String();
    }
}
