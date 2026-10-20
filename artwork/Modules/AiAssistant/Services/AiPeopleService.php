<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiContractRefData;
use Artwork\Modules\AiAssistant\DTOs\AiMonthMinutesData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonListData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonWorkloadData;
use Artwork\Modules\AiAssistant\DTOs\AiPlannedMinutesData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRemainingData;
use Artwork\Modules\AiAssistant\DTOs\AiRemainingWeekData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftRuleData;
use Artwork\Modules\AiAssistant\DTOs\AiWeekMinutesData;
use Artwork\Modules\AiAssistant\DTOs\AiWeekTargetData;
use Artwork\Modules\AiAssistant\DTOs\AiWorkloadAbsenceData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Personen fuer die Schichtplanung nachschlagen und ihre Auslastung lesen (ai-shift-planning).
 *
 * Personen sind Nutzer:innen, Freelancer und Dienstleister — alle drei koennen in `shift_workers`
 * stehen. Minuten kommen ausschliesslich aus `WorkTimeCalculationService::shiftMinutesPerDay()`
 * (effektive Zeiten der Zuweisung, Pause einmal am ersten Schichttag, Mitternachtsschichten
 * tageweise geteilt) — dieselbe Zahl, die Regelpruefung und Arbeitszeitkonto sehen.
 */
class AiPeopleService
{
    public const TYPE_USER = 'user';

    public const TYPE_FREELANCER = 'freelancer';

    public const TYPE_SERVICE_PROVIDER = 'service_provider';

    public const TYPES = [self::TYPE_USER, self::TYPE_FREELANCER, self::TYPE_SERVICE_PROVIDER];

    public const DEFAULT_LIMIT = 20;

    /** Vertragsregel, aus der sich eine Wochenobergrenze ablesen laesst (Stunden). */
    public const WEEKLY_MAX_RULE = 'weeklyMaxHours';

    public function __construct(
        private readonly ShiftRuleService $shiftRuleService,
        private readonly WorkTimeCalculationService $workTime,
    ) {
    }

    /**
     * @return class-string<User|Freelancer|ServiceProvider>|null
     */
    public static function classFor(string $type): ?string
    {
        return match ($type) {
            self::TYPE_USER => User::class,
            self::TYPE_FREELANCER => Freelancer::class,
            self::TYPE_SERVICE_PROVIDER => ServiceProvider::class,
            default => null,
        };
    }

    public static function typeOf(Model $person): string
    {
        return match (true) {
            $person instanceof User => self::TYPE_USER,
            $person instanceof Freelancer => self::TYPE_FREELANCER,
            default => self::TYPE_SERVICE_PROVIDER,
        };
    }

    public static function nameOf(Model $person): string
    {
        return trim(match (true) {
            $person instanceof User => $person->getFullNameAttribute(),
            $person instanceof Freelancer, $person instanceof ServiceProvider => $person->getNameAttribute(),
            default => '',
        });
    }

    /**
     * Person aus `{type, id}`; unbekannt → 404.
     */
    public function find(string $type, int $id): User|Freelancer|ServiceProvider
    {
        $class = self::classFor($type);
        abort_if($class === null, Response::HTTP_NOT_FOUND);

        /** @var User|Freelancer|ServiceProvider|null $person */
        $person = $class::query()->find($id);
        abort_if($person === null, Response::HTTP_NOT_FOUND);

        return $person;
    }

    /**
     * Suche ueber alle drei Personenarten, nur wer Schichten uebernehmen kann (`can_work_shifts`).
     */
    public function search(?string $search, ?int $limit): AiPersonListData
    {
        $limit = $this->limit($limit);
        $pattern = $search === null ? null : $this->contains($search);

        $queries = [
            User::query()->where('can_work_shifts', true)->when(
                $pattern !== null,
                static fn (Builder $query) => $query->where(static function (Builder $sub) use ($pattern): void {
                    $sub->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$pattern])
                        ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", [$pattern]);
                })
            ),
            Freelancer::query()->where('can_work_shifts', true)->when(
                $pattern !== null,
                static fn (Builder $query) => $query->where(static function (Builder $sub) use ($pattern): void {
                    $sub->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$pattern])
                        ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", [$pattern]);
                })
            ),
            ServiceProvider::query()->where('can_work_shifts', true)->when(
                $pattern !== null,
                static fn (Builder $query) => $query->where('provider_name', 'like', $pattern)
            ),
        ];

        $total = 0;
        $people = collect();

        foreach ($queries as $query) {
            $total += (clone $query)->count();
            // Je Art hoechstens `limit` Treffer laden; sortiert und gekappt wird danach gemeinsam.
            $people = $people->concat(
                $query->with(['assignedCrafts', 'shiftQualifications'])->limit($limit)->get()
            );
        }

        $people = $people
            ->sortBy(static fn (Model $person): string => mb_strtolower(self::nameOf($person)))
            ->take($limit)
            ->values();

        return new AiPersonListData(
            data: $people->map(fn (Model $person): AiPersonData => $this->personData($person))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    public function personData(Model $person): AiPersonData
    {
        /** @var User|Freelancer|ServiceProvider $person */
        $crafts = $person->assignedCrafts
            ->map(static fn (Craft $craft): AiRefData => new AiRefData(
                (int) $craft->id,
                AiText::truncate($craft->name)
            ))
            ->unique('id')
            ->values()
            ->all();

        $qualifications = $person->shiftQualifications
            ->map(static fn (ShiftQualification $qualification): AiRefData => new AiRefData(
                (int) $qualification->id,
                AiText::truncate($qualification->name)
            ))
            ->unique('id')
            ->values()
            ->all();

        return new AiPersonData(
            id: (int) $person->id,
            type: self::typeOf($person),
            name: (string) AiText::truncate(self::nameOf($person)),
            crafts: $crafts,
            qualifications: $qualifications,
            has_contract: $person instanceof User && $person->activeWorkContract() !== null,
        );
    }

    public function workload(
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): AiPersonWorkloadData {
        $weeks = self::weekKeysBetween($from, $to);
        $months = self::monthKeysBetween($from, $to);
        $perDay = $this->plannedMinutesPerDay($person, $from, $to);

        $weekMinutes = array_fill_keys($weeks, 0);
        $monthMinutes = array_fill_keys($months, 0);

        foreach ($perDay as $day => $minutes) {
            $date = CarbonImmutable::parse($day);
            $weekMinutes[self::weekKey($date)] = ($weekMinutes[self::weekKey($date)] ?? 0) + $minutes;
            $monthMinutes[self::monthKey($date)] = ($monthMinutes[self::monthKey($date)] ?? 0) + $minutes;
        }

        $weekMinutes = array_intersect_key($weekMinutes, array_flip($weeks));
        $monthMinutes = array_intersect_key($monthMinutes, array_flip($months));

        $rules = $person instanceof User ? $this->shiftRuleService->getActiveRulesForUser($person) : collect();
        $contract = $person instanceof User ? $person->activeWorkContract() : null;
        $targets = $person instanceof User ? $this->targetMinutesByWeek($person, $weeks) : null;

        return new AiPersonWorkloadData(
            person: $this->personData($person),
            contract: $contract === null ? null : new AiContractRefData((string) AiText::truncate($contract->name)),
            rules: $rules->map(static fn (ShiftRule $rule): AiShiftRuleData => new AiShiftRuleData(
                trigger_type: (string) $rule->trigger_type,
                label: (string) AiText::truncate($rule->name),
                value: $rule->individual_number_value === null ? null : (float) $rule->individual_number_value,
                period_weeks: $rule->period_weeks === null ? null : (int) $rule->period_weeks,
            ))->values()->all(),
            planned: new AiPlannedMinutesData(
                weeks: array_map(
                    static fn (string $week): AiWeekMinutesData => new AiWeekMinutesData($week, $weekMinutes[$week]),
                    $weeks
                ),
                months: array_map(
                    static fn (string $month): AiMonthMinutesData => new AiMonthMinutesData(
                        $month,
                        $monthMinutes[$month]
                    ),
                    $months
                ),
            ),
            target_minutes_by_week: $targets === null ? null : array_map(
                static fn (string $week): AiWeekTargetData => new AiWeekTargetData($week, $targets[$week] ?? null),
                $weeks
            ),
            absences: $this->absences($person, $from, $to),
            remaining: $this->remaining($weeks, $weekMinutes, $rules, $targets),
        );
    }

    /**
     * Freie Kapazitaet einer ISO-Woche — dieselbe Rechnung wie `remaining` am Workload.
     */
    public function weekCapacity(User|Freelancer|ServiceProvider $person, CarbonImmutable $day): AiRemainingWeekData
    {
        $weekStart = $day->startOfWeek(Carbon::MONDAY)->startOfDay();
        $week = self::weekKey($weekStart);
        $planned = array_sum($this->workTime->shiftMinutesPerDay(
            $person,
            Carbon::parse($weekStart->toDateString()),
            Carbon::parse($weekStart->addDays(6)->toDateString())
        ));
        $rules = $person instanceof User ? $this->shiftRuleService->getActiveRulesForUser($person) : collect();
        $targets = $person instanceof User ? $this->targetMinutesByWeek($person, [$week]) : null;

        return $this->remaining([$week], [$week => (int) $planned], $rules, $targets)->weeks[0];
    }

    /**
     * Geplante effektive Minuten je Tag ueber alle ISO-Wochen und Kalendermonate, die der Zeitraum
     * beruehrt ('Y-m-d' => Minuten).
     *
     * @return array<string, int>
     */
    public function plannedMinutesPerDay(
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): array {
        [$start, $end] = self::coveringRange($from, $to);

        /** @var array<string, int> $minutes */
        $minutes = $this->workTime->shiftMinutesPerDay(
            $person,
            Carbon::parse($start->toDateString()),
            Carbon::parse($end->toDateString())
        );

        return $minutes;
    }

    /**
     * Erster und letzter Tag der Vereinigung aus allen beruehrten ISO-Wochen und Monaten.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function coveringRange(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $start = $from->startOfWeek(Carbon::MONDAY)->min($from->startOfMonth())->startOfDay();
        $end = $to->endOfWeek(Carbon::SUNDAY)->max($to->endOfMonth())->startOfDay();

        return [$start, $end];
    }

    public static function weekKey(CarbonImmutable|Carbon $date): string
    {
        return $date->format('o-\WW');
    }

    public static function monthKey(CarbonImmutable|Carbon $date): string
    {
        return $date->format('Y-m');
    }

    /**
     * @return list<string>
     */
    public static function weekKeysBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $keys = [];
        $cursor = $from->startOfWeek(Carbon::MONDAY)->startOfDay();
        $last = $to->startOfDay();

        while ($cursor->lessThanOrEqualTo($last)) {
            $keys[] = self::weekKey($cursor);
            $cursor = $cursor->addWeek();
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    public static function monthKeysBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $keys = [];
        $cursor = $from->startOfMonth()->startOfDay();
        $last = $to->startOfDay();

        while ($cursor->lessThanOrEqualTo($last)) {
            $keys[] = self::monthKey($cursor);
            $cursor = $cursor->addMonthNoOverflow();
        }

        return $keys;
    }

    /**
     * Sollzeit je ISO-Woche aus dem Arbeitszeitmuster (ohne Buchungen); `null` je Woche, wenn an
     * einem Tag kein Muster gilt.
     *
     * @param list<string> $weeks
     * @return array<string, int|null>
     */
    private function targetMinutesByWeek(User $user, array $weeks): array
    {
        if ($weeks === []) {
            return [];
        }

        $start = CarbonImmutable::parse($weeks[0] . '-1')->startOfDay();
        $end = CarbonImmutable::parse($weeks[count($weeks) - 1] . '-7')->startOfDay();

        $byWeek = [];
        foreach (
            $this->workTime->breakdownForRange(
                $user,
                Carbon::parse($start->toDateString()),
                Carbon::parse($end->toDateString()),
                ['use_bookings' => false]
            ) as $day => $breakdown
        ) {
            $byWeek[self::weekKey(CarbonImmutable::parse((string) $day))][] = $breakdown;
        }

        $targets = [];
        foreach ($weeks as $week) {
            $targets[$week] = WorkTimeCalculationService::summarizeRange($byWeek[$week] ?? [])['target'];
        }

        return $targets;
    }

    /**
     * @param list<string> $weeks
     * @param array<string, int> $planned
     * @param Collection<int, ShiftRule> $rules
     * @param array<string, int|null>|null $targets
     */
    private function remaining(array $weeks, array $planned, Collection $rules, ?array $targets): AiRemainingData
    {
        $weeklyMax = $rules->firstWhere('trigger_type', self::WEEKLY_MAX_RULE);
        $ruleMinutes = $weeklyMax === null ? null : (int) round((float) $weeklyMax->individual_number_value * 60);

        return new AiRemainingData(array_map(
            static function (string $week) use ($planned, $ruleMinutes, $targets): AiRemainingWeekData {
                $limitedBy = null;
                $max = null;

                if ($ruleMinutes !== null) {
                    [$max, $limitedBy] = [$ruleMinutes, self::WEEKLY_MAX_RULE];
                } elseif (($targets[$week] ?? null) !== null) {
                    [$max, $limitedBy] = [(int) $targets[$week], 'target'];
                }

                return new AiRemainingWeekData(
                    week: $week,
                    minutes_planned: $planned[$week] ?? 0,
                    minutes_max: $max,
                    minutes_remaining: $max === null ? null : max(0, $max - ($planned[$week] ?? 0)),
                    limited_by: $limitedBy,
                );
            },
            $weeks
        ));
    }

    /**
     * @return list<AiWorkloadAbsenceData>
     */
    public function absences(Model $person, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $vacations = Vacation::query()
            ->without(['series', 'conflicts'])
            ->where('vacationer_type', $person->getMorphClass())
            ->where('vacationer_id', $person->getKey())
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->whereIn('type', [
                VacationType::OFF_WORK->value,
                VacationType::FREE_WORK->value,
                VacationType::NOT_AVAILABLE->value,
            ])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $timezone = (string) config('app.timezone');
        /** @var list<array{from: CarbonImmutable, to: CarbonImmutable, type: string, full_day: bool}> $ranges */
        $ranges = [];

        foreach ($vacations as $vacation) {
            $type = $vacation->type instanceof VacationType ? $vacation->type->value : (string) $vacation->type;
            $date = CarbonImmutable::parse($vacation->date, $timezone)->startOfDay();
            $fullDay = (bool) $vacation->full_day || !$vacation->start_time || !$vacation->end_time;

            if ($fullDay) {
                $last = $ranges === [] ? null : $ranges[count($ranges) - 1];

                if (
                    $last !== null && $last['full_day'] && $last['type'] === $type
                    && $last['to']->addDay()->isSameDay($date)
                ) {
                    $ranges[count($ranges) - 1]['to'] = $date->setTime(23, 59);
                    continue;
                }

                $ranges[] = ['from' => $date, 'to' => $date->setTime(23, 59), 'type' => $type, 'full_day' => true];
                continue;
            }

            $start = $date->setTimeFromTimeString($vacation->start_time->format('H:i'));
            $end = $date->setTimeFromTimeString($vacation->end_time->format('H:i'));
            if ($end->lessThanOrEqualTo($start)) {
                $end = $end->addDay();
            }

            $ranges[] = ['from' => $start, 'to' => $end, 'type' => $type, 'full_day' => false];
        }

        return array_map(
            static fn (array $range): AiWorkloadAbsenceData => new AiWorkloadAbsenceData(
                from: $range['from']->toIso8601String(),
                to: $range['to']->toIso8601String(),
                type: $range['type'],
            ),
            $ranges
        );
    }

    /**
     * Suchmuster fuer `LIKE`: Platzhalter im Suchwort bleiben Text (wie `AiLookupReadService`).
     */
    private function contains(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.list_limit');

        if ($requested === null || $requested < 1) {
            return min(self::DEFAULT_LIMIT, $max);
        }

        return min($requested, $max);
    }
}
