<?php

namespace Artwork\Modules\WorkTime\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Holidays\Services\SpecialDayService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Artwork\Modules\User\Services\ThreeMonthAverageTargetService;
use Carbon\Carbon;

/**
 * EINZIGE Quelle für Tageswerte (Soll/Ist) im Arbeitszeitkonto.
 *
 * Soll (TVöD als Referenz):
 *  - Arbeitszeitmuster, das zum Datum gültig ist (valid_from/valid_until); Wochentag ohne Zeit = 0.
 *  - Ohne Muster: weekly_working_hours / 5 an Mo–Fr, 0 an Sa/So (Fünftagewoche).
 *  - Sondertag (Feiertag mit Flag treatAsSpecialDay) OHNE Arbeit und aktiver Sondertag-Regel im
 *    Vertrag: Soll 0 bzw. im Dreimonatsmodus Soll minus Wochentagsdurchschnitt. Arbeit am
 *    Sondertag = keine Minderung. Schulferien tragen das Flag nicht und senken das Soll nie.
 *  - Ersatzfreier Tag für einen Sondertag (CompensationDayOff for_holiday) mindert wie bisher
 *    unabhängig von geleisteter Arbeit (Voll- bzw. Dreimonatslogik).
 *  - Krank (NOT_AVAILABLE) und Urlaub (OFF_WORK) lassen das Soll stehen.
 *
 * Ist:
 *  - Tag mit Nachtbuchung (work_time_bookings): worked_hours der Buchung (keine Doppelzählung).
 *  - Sonst Schichtminuten (Pause einmal am ersten Schichttag) plus Individualzeiten.
 *  - Krank/Urlaub sind soll-neutral: ganzer Tag -> Ist = Soll; Halbtag -> Arbeit + 0,5 · Soll.
 */
class WorkTimeCalculationService
{
    public const REASON_SPECIAL_DAY = 'special_day';
    public const REASON_COMPENSATION_DAY = 'compensation_day';

    private const WEEKDAYS = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
    ];

    public function __construct(
        private readonly SpecialDayService $specialDayService,
        private readonly ThreeMonthAverageTargetService $threeMonthAverageTargetService,
        private readonly ContractSettingsResolver $contractSettings,
    ) {
    }

    // ------------------------------------------------------------------
    // Öffentliche API
    // ------------------------------------------------------------------

    public function targetMinutes(User|Freelancer|ServiceProvider $entity, Carbon $day, ?array $context = null): int
    {
        return $this->dayBreakdown($entity, $day, $context)['target'];
    }

    public function actualMinutes(User|Freelancer|ServiceProvider $entity, Carbon $day, ?array $context = null): int
    {
        return $this->dayBreakdown($entity, $day, $context)['actual'];
    }

    /**
     * Alle Tage eines Zeitraums, 'Y-m-d' => Breakdown (Vorab-Laden, kein N+1).
     *
     * Optionen: use_bookings (bool, default true), special_days (array 'Y-m-d' => Name),
     * holiday_comp_days (iterable<CompensationDayOff> für diese Person).
     *
     * @return array<string, array<string, mixed>>
     */
    public function breakdownForRange(
        User|Freelancer|ServiceProvider $entity,
        Carbon $start,
        Carbon $end,
        array $options = []
    ): array {
        $context = $this->buildContext($entity, $start, $end, $options);
        $result = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $result[$cursor->toDateString()] = $this->dayBreakdown($entity, $cursor, $context);
            $cursor->addDay();
        }

        return $result;
    }

    /**
     * @return array{
     *     date: string, target: int, actual: int, base_target: int, balance: int,
     *     work_minutes: int, shift_minutes: int, individual_minutes: int, nightly_minutes: int,
     *     is_special_day: bool, special_day_name: string|null, special_day_counts: bool,
     *     target_reduction: int, reduction_reason: string|null,
     *     reference_period: array{start: string, end: string}|null, reference_weekday_average: int|null,
     *     is_sick: bool, is_vacation: bool, vacation_factor: float, sick_factor: float,
     *     has_booking: bool, booking: array<string, mixed>|null
     * }
     */
    public function dayBreakdown(User|Freelancer|ServiceProvider $entity, Carbon $day, ?array $context = null): array
    {
        $day = $day->copy()->startOfDay();
        $key = $day->toDateString();

        if ($context === null || !$this->contextCovers($context, $key)) {
            $context = $this->buildContext($entity, $day, $day);
        }

        $baseTarget = $this->baseTargetMinutes($entity, $day, $context);
        $shiftMinutes = (int) ($context['shift_minutes'][$key] ?? 0);
        $individualMinutes = (int) ($context['individual_minutes'][$key] ?? 0);
        $workMinutes = $shiftMinutes + $individualMinutes;

        $booking = $context['bookings'][$key] ?? null;
        $absence = $context['absences'][$key] ?? null;
        $sickFactor = (float) ($absence['sick_factor'] ?? 0.0);
        $vacationFactor = (float) ($absence['vacation_factor'] ?? 0.0);
        $neutralFactor = min(1.0, $sickFactor + $vacationFactor);

        $isSpecialDay = array_key_exists($key, $context['special_days'] ?? []);
        $specialDayName = $isSpecialDay ? ($context['special_days'][$key] ?? null) : null;
        $specialDayCounts = $isSpecialDay && (bool) ($context['special_day_rule_active'] ?? false);
        $threeMonthMode = (bool) ($context['three_month_mode'] ?? false);

        $reduction = 0;
        $reason = null;
        $referencePeriod = null;
        $referenceAverage = null;

        if ($entity instanceof User && $baseTarget > 0) {
            if ($specialDayCounts) {
                // Nur Sondertage OHNE Arbeit senken das Soll; geleistete Stunden zählen normal.
                if ($workMinutes === 0) {
                    if ($threeMonthMode) {
                        $referenceAverage = $this->threeMonthAverageTargetService
                            ->averageMinutesFor($entity, $day, $baseTarget);
                        $referencePeriod = $this->threeMonthAverageTargetService->referencePeriodFor($day);
                        $reduction = min($baseTarget, max(0, $referenceAverage));
                    } else {
                        $reduction = $baseTarget;
                    }
                    $reason = self::REASON_SPECIAL_DAY;
                }
            } else {
                $compValue = (float) ($context['holiday_comp'][$key] ?? 0.0);
                if ($compValue > 0) {
                    $reduction = $this->threeMonthAverageTargetService->reductionMinutesFor(
                        $entity,
                        $day,
                        min(1.0, $compValue),
                        $baseTarget,
                        $threeMonthMode
                    );
                    if ($threeMonthMode) {
                        $referenceAverage = $this->threeMonthAverageTargetService
                            ->averageMinutesFor($entity, $day, $baseTarget);
                        $referencePeriod = $this->threeMonthAverageTargetService->referencePeriodFor($day);
                    }
                    $reason = self::REASON_COMPENSATION_DAY;
                }
            }
        }

        $target = max(0, $baseTarget - $reduction);

        if ($booking !== null) {
            $actual = (int) $booking['worked'];
        } elseif ($neutralFactor >= 1.0) {
            $actual = $target;
        } elseif ($neutralFactor > 0.0) {
            $actual = $workMinutes + (int) round($target * $neutralFactor);
        } else {
            $actual = $workMinutes;
        }

        return [
            'date' => $key,
            'target' => $target,
            'actual' => $actual,
            'base_target' => $baseTarget,
            'balance' => $actual - $target,
            'work_minutes' => $workMinutes,
            'shift_minutes' => $shiftMinutes,
            'individual_minutes' => $individualMinutes,
            'nightly_minutes' => (int) ($booking['night'] ?? 0),
            'is_special_day' => $isSpecialDay,
            'special_day_name' => $specialDayName,
            'special_day_counts' => $specialDayCounts,
            'target_reduction' => $reduction,
            'reduction_reason' => $reason,
            'reference_period' => $referencePeriod,
            'reference_weekday_average' => $referenceAverage,
            'is_sick' => $sickFactor > 0.0,
            'is_vacation' => $vacationFactor > 0.0,
            'vacation_factor' => $vacationFactor,
            'sick_factor' => $sickFactor,
            'has_booking' => $booking !== null,
            'booking' => $booking,
        ];
    }

    /**
     * Vorab-Laden aller Tagesdaten eines Zeitraums für EINE Person. Geladene Relationen
     * (shifts, individualTimes, workTimeBookings, vacations, workTimes, contract) werden
     * genutzt, sonst je Relation genau eine Query.
     *
     * @return array<string, mixed>
     */
    public function buildContext(
        User|Freelancer|ServiceProvider $entity,
        Carbon $start,
        Carbon $end,
        array $options = []
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        if ($end->lt($start)) {
            $end = $start->copy();
        }
        $useBookings = (bool) ($options['use_bookings'] ?? true);
        $isUser = $entity instanceof User;

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'shift_minutes' => $this->shiftMinutesPerDay($entity, $start, $end),
            'individual_minutes' => $this->individualMinutesPerDay($entity, $start, $end),
            'bookings' => $isUser && $useBookings ? $this->bookingsPerDay($entity, $start, $end) : [],
            'absences' => $this->absencesPerDay($entity, $start, $end),
            'special_days' => $isUser
                ? ($options['special_days'] ?? $this->specialDayService->specialDaysBetween($start, $end))
                : [],
            'special_day_rule_active' => $isUser && $this->specialDayService->specialDayRuleActiveFor($entity),
            'three_month_mode' => $isUser && $this->threeMonthAverageTargetService->usesThreeMonthAverage($entity),
            'patterns' => $isUser ? $this->patternsPerDay($entity, $start, $end) : [],
            'holiday_comp' => $isUser
                ? $this->holidayCompensationPerDay($entity, $start, $end, $options['holiday_comp_days'] ?? null)
                : [],
            'weekly_working_hours' => (float) ($entity->getAttribute('weekly_working_hours') ?? 0),
        ];
    }

    /**
     * Schichtminuten je Tag (Pause einmal am ersten Schichttag), 'Y-m-d' => Minuten.
     *
     * @return array<string, int>
     */
    public function shiftMinutesPerDay(User|Freelancer|ServiceProvider $entity, Carbon $start, Carbon $end): array
    {
        $shiftMinutesPerDay = [];
        $rangeStartTimestamp = strtotime($start->toDateString() . ' 00:00:00');
        // Tagesgrenze exklusiv um 24:00, sonst fehlt bei Über-Mitternacht-Schichten die Minute 23:59
        $rangeEndTimestamp = strtotime($end->toDateString() . ' 00:00:00') + 86400;

        $dayTimestamps = [];
        $ts = $rangeStartTimestamp;
        while ($ts < $rangeEndTimestamp) {
            $dateStr = date('Y-m-d', $ts);
            $shiftMinutesPerDay[$dateStr] = 0;
            $dayTimestamps[$dateStr] = [
                'start' => $ts,
                'end' => $ts + 86400,
            ];
            $ts += 86400;
        }

        foreach ($this->shiftsFor($entity, $start, $end) as $shift) {
            $pivot = $shift->pivot;
            $sDateStr = $pivot->start_date ?? $shift->start_date ?? null;
            $eDateStr = $pivot->end_date ?? $shift->end_date ?? null;
            $sTimeStr = $pivot->start_time ?? $shift->start ?? null;
            $eTimeStr = $pivot->end_time ?? $shift->end ?? null;

            if (!$sDateStr || !$sTimeStr || !$eDateStr || !$eTimeStr) {
                continue;
            }

            $sDateOnly = self::dateOnly($sDateStr);
            $eDateOnly = self::dateOnly($eDateStr);
            $sTime = self::timeOnly($sTimeStr);
            $eTime = self::timeOnly($eTimeStr);

            $shiftStartTs = strtotime("{$sDateOnly} {$sTime}");
            $shiftEndTs = strtotime("{$eDateOnly} {$eTime}");

            if ($shiftStartTs === false || $shiftEndTs === false) {
                continue;
            }
            if ($shiftEndTs <= $rangeStartTimestamp || $shiftStartTs >= $rangeEndTimestamp) {
                continue;
            }

            $breakMinutes = (int) ($shift->break_minutes ?? 0);
            $firstDayStr = date('Y-m-d', max($shiftStartTs, $rangeStartTimestamp));
            $lastDayStr = date('Y-m-d', min($shiftEndTs - 1, $rangeEndTimestamp - 1));
            // Pause nur am ersten Tag der Schicht abziehen – auch wenn der erste Tag vor dem Zeitraum liegt
            $shiftFirstDayStr = date('Y-m-d', $shiftStartTs);

            $dayTs = strtotime($firstDayStr);
            $lastDayTs = strtotime($lastDayStr);

            while ($dayTs <= $lastDayTs) {
                $dateStr = date('Y-m-d', $dayTs);
                $dayStartTimestamp = $dayTimestamps[$dateStr]['start'] ?? $dayTs;
                $dayEndTimestamp = $dayTimestamps[$dateStr]['end'] ?? ($dayTs + 86400);

                $workStartTimestamp = max($shiftStartTs, $dayStartTimestamp);
                $workEndTimestamp = min($shiftEndTs, $dayEndTimestamp);

                if ($workStartTimestamp < $workEndTimestamp) {
                    $duration = (int) (($workEndTimestamp - $workStartTimestamp) / 60);
                    if ($dateStr === $shiftFirstDayStr) {
                        $duration -= $breakMinutes;
                    }
                    $shiftMinutesPerDay[$dateStr] = ($shiftMinutesPerDay[$dateStr] ?? 0) + max(0, $duration);
                }

                $dayTs += 86400;
            }
        }

        return $shiftMinutesPerDay;
    }

    /**
     * Arbeitszeitmuster, das am Tag gültig ist (neuestes valid_from gewinnt) oder null.
     */
    public function patternForDate(User $user, Carbon $day): ?UserWorkTime
    {
        $patterns = $this->patternsPerDay($user, $day, $day);

        return $patterns[$day->toDateString()] ?? null;
    }

    /**
     * Nur das Basis-Tagessoll je Tag (Muster bzw. Fünftagewoche), ohne Schichten/Buchungen zu laden.
     *
     * @return array<string, int> 'Y-m-d' => Minuten
     */
    public function baseTargetsForRange(User $user, Carbon $start, Carbon $end): array
    {
        $context = [
            'patterns' => $this->patternsPerDay($user, $start, $end),
            'weekly_working_hours' => (float) ($user->getAttribute('weekly_working_hours') ?? 0),
        ];
        $result = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $result[$cursor->toDateString()] = $this->baseTargetMinutes($user, $cursor, $context);
            $cursor->addDay();
        }

        return $result;
    }

    /**
     * Basis-Tagessoll (vor Sondertag-/Ausgleichstag-Minderung).
     */
    public function baseTargetMinutes(User|Freelancer|ServiceProvider $entity, Carbon $day, ?array $context = null): int
    {
        $key = $day->toDateString();
        $weekday = self::WEEKDAYS[$day->dayOfWeek];

        if ($entity instanceof User) {
            $patterns = $context !== null && array_key_exists('patterns', $context)
                ? $context['patterns']
                : $this->patternsPerDay($entity, $day, $day);
            $pattern = $patterns[$key] ?? null;
            if ($pattern instanceof UserWorkTime) {
                return self::patternDayMinutes($pattern, $weekday);
            }
        }

        $weekly = (float) ($context['weekly_working_hours'] ?? ($entity->getAttribute('weekly_working_hours') ?? 0));

        return $day->isWeekday() ? (int) round($weekly * 60 / 5) : 0;
    }

    /**
     * AZK-Badge-Format mit Vorzeichen: "+10:30 h" / "−2:00 h" (echtes Minus U+2212).
     */
    public static function formatSignedHours(int $minutes): string
    {
        $abs = abs($minutes);
        $sign = $minutes < 0 ? "\u{2212}" : '+';

        return sprintf('%s%d:%02d h', $sign, intdiv($abs, 60), $abs % 60);
    }

    /**
     * Unsigniertes Stundenformat "38:00 h" (Geplant/Soll im Dienstplan); negative Werte
     * erhalten ein echtes Minus (U+2212), nie ein Plus.
     */
    public static function formatHours(int $minutes): string
    {
        $abs = abs($minutes);
        $sign = $minutes < 0 ? "\u{2212}" : '';

        return sprintf('%s%d:%02d h', $sign, intdiv($abs, 60), $abs % 60);
    }

    // ------------------------------------------------------------------
    // Vorab-Laden
    // ------------------------------------------------------------------

    private function contextCovers(array $context, string $dateKey): bool
    {
        return isset($context['start'], $context['end'])
            && $dateKey >= $context['start']
            && $dateKey <= $context['end'];
    }

    /**
     * @return iterable<int, mixed>
     */
    private function shiftsFor(User|Freelancer|ServiceProvider $entity, Carbon $start, Carbon $end): iterable
    {
        if ($entity->relationLoaded('shifts')) {
            return $entity->shifts;
        }

        return $entity->shifts()
            ->where('shifts.start_date', '<=', $end->toDateString())
            ->where('shifts.end_date', '>=', $start->toDateString())
            ->get();
    }

    /**
     * Individualzeiten je Tag, 'Y-m-d' => Minuten (nur Tage im Zeitraum).
     *
     * Einträge MIT Uhrzeiten werden wie Schichten tageweise zugeschnitten (Tagesgrenze exklusiv
     * 24:00, Pause einmal am ersten Tag) – eine Über-Mitternacht-Zeit 22:00–04:00 zählt also nicht
     * mehr an beiden Tagen voll, sondern 2 h am ersten und 4 h am zweiten Tag.
     *
     * Einträge OHNE Uhrzeiten (full_day bzw. fehlende Zeiten) tragen ihre Dauer nur in
     * `working_time_minutes`; dieser Wert bezieht sich laut Model auf den ganzen Eintrag, nicht
     * auf einen Tag. Bei mehrtägigen Einträgen wird er deshalb gleichmäßig auf die Tage verteilt
     * (Rest minutenweise auf die ersten Tage), damit die Summe über alle Tage dem Eintrag entspricht.
     * Eintägige Einträge bleiben in beiden Fällen unverändert.
     *
     * Öffentlich, damit die Regelprüfung dieselbe Tageszuordnung nutzen kann.
     *
     * @return array<string, int>
     */
    public function individualMinutesPerDay(User|Freelancer|ServiceProvider $entity, Carbon $start, Carbon $end): array
    {
        $individualTimes = $entity->relationLoaded('individualTimes')
            ? $entity->individualTimes
            : $entity->individualTimes()
                ->individualByDateRange($start->toDateString(), $end->toDateString())
                ->get();

        $startKey = $start->toDateString();
        $endKey = $end->toDateString();
        $rangeStartTimestamp = strtotime($startKey . ' 00:00:00');
        // Tagesgrenze exklusiv um 24:00 (wie shiftMinutesPerDay)
        $rangeEndTimestamp = strtotime($endKey . ' 00:00:00') + 86400;
        $result = [];

        foreach ($individualTimes as $individualTime) {
            $days = [];
            foreach (($individualTime->days_of_individual_time ?? []) as $day) {
                if ($day !== null && is_scalar($day)) {
                    $days[] = (string) $day;
                }
            }
            if ($days === []) {
                continue;
            }

            $hasTimes = !(bool) ($individualTime->full_day ?? false)
                && !empty($individualTime->start_time)
                && !empty($individualTime->end_time)
                && !empty($individualTime->start_date)
                && !empty($individualTime->end_date);

            if ($hasTimes) {
                $timeStartTs = strtotime(
                    self::dateOnly($individualTime->start_date) . ' ' . self::timeOnly($individualTime->start_time)
                );
                $timeEndTs = strtotime(
                    self::dateOnly($individualTime->end_date) . ' ' . self::timeOnly($individualTime->end_time)
                );

                if ($timeStartTs !== false && $timeEndTs !== false && $timeEndTs > $timeStartTs) {
                    if ($timeEndTs <= $rangeStartTimestamp || $timeStartTs >= $rangeEndTimestamp) {
                        continue;
                    }

                    $breakMinutes = max(0, (int) ($individualTime->break_minutes ?? 0));
                    // Pause nur am ersten Tag des Eintrags – auch wenn der vor dem Zeitraum liegt
                    $entryFirstDay = date('Y-m-d', $timeStartTs);
                    $dayTs = strtotime(date('Y-m-d', max($timeStartTs, $rangeStartTimestamp)));
                    $lastDayTs = strtotime(date('Y-m-d', min($timeEndTs - 1, $rangeEndTimestamp - 1)));

                    while ($dayTs <= $lastDayTs) {
                        $dateStr = date('Y-m-d', $dayTs);
                        $workStart = max($timeStartTs, $dayTs);
                        $workEnd = min($timeEndTs, $dayTs + 86400);
                        if ($workStart < $workEnd) {
                            $duration = intdiv($workEnd - $workStart, 60);
                            if ($dateStr === $entryFirstDay) {
                                $duration -= $breakMinutes;
                            }
                            $result[$dateStr] = ($result[$dateStr] ?? 0) + max(0, $duration);
                        }
                        $dayTs += 86400;
                    }

                    continue;
                }
            }

            // Ohne (gültige) Uhrzeiten: working_time_minutes gleichmäßig auf die Tage des Eintrags verteilen
            $totalMinutes = max(0, (int) ($individualTime->working_time_minutes ?? 0));
            $dayCount = count($days);
            $perDay = intdiv($totalMinutes, $dayCount);
            $remainder = $totalMinutes % $dayCount;

            foreach ($days as $index => $dayKey) {
                if ($dayKey < $startKey || $dayKey > $endKey) {
                    continue;
                }
                $result[$dayKey] = ($result[$dayKey] ?? 0) + $perDay + ($index < $remainder ? 1 : 0);
            }
        }

        return $result;
    }

    /**
     * @return array<string, array{worked: int, wanted: int, night: int, balance_change: int, is_special_day: bool}>
     */
    private function bookingsPerDay(User $user, Carbon $start, Carbon $end): array
    {
        $startKey = $start->toDateString();
        $endKey = $end->toDateString();

        $bookings = $user->relationLoaded('workTimeBookings')
            ? $user->workTimeBookings
            : $user->workTimeBookings()->whereBetween('booking_day', [$startKey, $endKey])->get();

        $result = [];
        foreach ($bookings as $booking) {
            $bookingDay = $booking->booking_day;
            if ($bookingDay === null) {
                continue;
            }
            $dayKey = $bookingDay instanceof \DateTimeInterface
                ? $bookingDay->format('Y-m-d')
                : self::dateOnly((string) $bookingDay);
            if ($dayKey < $startKey || $dayKey > $endKey) {
                continue;
            }

            $entry = $result[$dayKey] ?? [
                'worked' => 0,
                'wanted' => 0,
                'night' => 0,
                'balance_change' => 0,
                'is_special_day' => false,
            ];
            $entry['worked'] += (int) $booking->worked_hours;
            $entry['wanted'] += (int) $booking->wanted_working_hours;
            $entry['night'] += (int) $booking->nightly_working_hours;
            $entry['balance_change'] += (int) $booking->work_time_balance_change;
            $entry['is_special_day'] = $entry['is_special_day'] || (bool) $booking->is_special_day;
            $result[$dayKey] = $entry;
        }

        return $result;
    }

    /**
     * Krank (NOT_AVAILABLE) und Urlaub (OFF_WORK) je Tag; ganzer Tag = 1.0, halber Tag = 0.5.
     *
     * @return array<string, array{sick_factor: float, vacation_factor: float}>
     */
    private function absencesPerDay(User|Freelancer|ServiceProvider $entity, Carbon $start, Carbon $end): array
    {
        if (!method_exists($entity, 'vacations')) {
            return [];
        }

        $startKey = $start->toDateString();
        $endKey = $end->toDateString();

        $vacations = $entity->relationLoaded('vacations')
            ? $entity->vacations
            : $entity->vacations()->whereBetween('date', [$startKey, $endKey])->get();

        $result = [];
        foreach ($vacations as $vacation) {
            if (!$vacation->date) {
                continue;
            }
            $dayKey = $vacation->date instanceof \DateTimeInterface
                ? $vacation->date->format('Y-m-d')
                : self::dateOnly((string) $vacation->date);
            if ($dayKey < $startKey || $dayKey > $endKey) {
                continue;
            }

            $factor = ($vacation->full_day ?? true) ? 1.0 : 0.5;
            $entry = $result[$dayKey] ?? ['sick_factor' => 0.0, 'vacation_factor' => 0.0];

            $type = $vacation->type instanceof \BackedEnum ? $vacation->type->value : $vacation->type;
            if ($type === 'NOT_AVAILABLE') {
                $entry['sick_factor'] = min(1.0, $entry['sick_factor'] + $factor);
            } elseif ($type === 'OFF_WORK' || $vacation->comment === 'OFF_WORK') {
                $entry['vacation_factor'] = min(1.0, $entry['vacation_factor'] + $factor);
            } else {
                continue;
            }

            $result[$dayKey] = $entry;
        }

        return $result;
    }

    /**
     * @return array<string, UserWorkTime|null>
     */
    private function patternsPerDay(User $user, Carbon $start, Carbon $end): array
    {
        $workTimes = $user->relationLoaded('workTimes')
            ? $user->workTimes
            : $user->workTimes()
                ->where(function ($q) use ($end): void {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $end->toDateString());
                })
                ->where(function ($q) use ($start): void {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', $start->toDateString());
                })
                ->get();

        $parsed = [];
        foreach ($workTimes as $workTime) {
            $parsed[] = [
                'valid_from' => $workTime->valid_from
                    ? Carbon::parse($workTime->valid_from)->startOfDay()->timestamp
                    : null,
                'valid_until' => $workTime->valid_until
                    ? Carbon::parse($workTime->valid_until)->endOfDay()->timestamp
                    : null,
                'workTime' => $workTime,
            ];
        }
        // neuestes valid_from zuerst, damit das aktuellste gültige Muster gewinnt
        usort($parsed, static fn (array $a, array $b): int => ($b['valid_from'] ?? 0) <=> ($a['valid_from'] ?? 0));

        $result = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $ts = $cursor->timestamp;
            $active = null;
            foreach ($parsed as $candidate) {
                if (
                    ($candidate['valid_from'] === null || $candidate['valid_from'] <= $ts)
                    && ($candidate['valid_until'] === null || $candidate['valid_until'] >= $ts)
                ) {
                    $active = $candidate['workTime'];
                    break;
                }
            }
            $result[$cursor->toDateString()] = $active;
            $cursor->addDay();
        }

        return $result;
    }

    /**
     * @param iterable<int, CompensationDayOff>|null $preloaded
     * @return array<string, float>
     */
    private function holidayCompensationPerDay(User $user, Carbon $start, Carbon $end, ?iterable $preloaded): array
    {
        $days = $preloaded ?? CompensationDayOff::query()
            ->where('user_id', $user->id)
            ->where('for_holiday', true)
            ->whereNotNull('granted_date')
            ->whereBetween('granted_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $result = [];
        foreach ($days as $compDay) {
            if (!$compDay->granted_date || !$compDay->for_holiday) {
                continue;
            }
            $dayKey = $compDay->granted_date instanceof \DateTimeInterface
                ? $compDay->granted_date->format('Y-m-d')
                : self::dateOnly((string) $compDay->granted_date);
            $result[$dayKey] = ($result[$dayKey] ?? 0.0) + (float) $compDay->value;
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Helfer
    // ------------------------------------------------------------------

    private static function patternDayMinutes(UserWorkTime $workTime, string $weekday): int
    {
        $time = $workTime->{$weekday};

        if ($time === null && $workTime->work_time_pattern_id) {
            // Zeile ohne eigene Zeiten = reine Referenz auf die Vorlage
            $hasOwnTimes = false;
            foreach (self::WEEKDAYS as $name) {
                if ($workTime->{$name} !== null) {
                    $hasOwnTimes = true;
                    break;
                }
            }
            if (!$hasOwnTimes) {
                $time = $workTime->workTimePattern?->{$weekday};
            }
        }

        if ($time === null) {
            return 0;
        }
        if ($time instanceof \DateTimeInterface) {
            return (int) $time->format('G') * 60 + (int) $time->format('i');
        }

        $parts = explode(':', (string) $time);

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }

    private static function dateOnly(mixed $value): string
    {
        $string = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $string, $m)) {
            return $m[1];
        }

        return date('Y-m-d', strtotime($string) ?: 0);
    }

    private static function timeOnly(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }
        $string = (string) $value;
        if (preg_match('/\d{2}:\d{2}/', $string)) {
            return substr($string, 0, 8);
        }

        return date('H:i:s', strtotime($string) ?: 0);
    }
}
