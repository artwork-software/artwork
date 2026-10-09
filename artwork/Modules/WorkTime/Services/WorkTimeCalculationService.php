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
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * EINZIGE Quelle für Tageswerte (Soll/Ist) im Arbeitszeitkonto.
 *
 * Soll (TVöD als Referenz):
 *  - Arbeitszeitmuster, das zum Datum gültig ist (valid_from/valid_until); Wochentag ohne Zeit = 0.
 *  - Ohne gültiges Muster ist das Soll UNBEKANNT (null, Flag target_unknown) – es gibt keinen
 *    Fallback mehr auf users.weekly_working_hours (Entscheidung Block 4: das Arbeitszeitmuster ist
 *    die einzige Quelle für das Soll). Externe (Freelancer/Dienstleister) haben kein Soll (0).
 *  - Sondertag (Feiertag mit Flag treatAsSpecialDay) OHNE Arbeit und aktiver Sondertag-Regel im
 *    Vertrag: Soll 0 bzw. im Dreimonatsmodus Soll minus Wochentagsdurchschnitt. Arbeit am
 *    Sondertag = keine Minderung. Schulferien tragen das Flag nicht und senken das Soll nie.
 *  - Ersatzfreier Tag für einen Sondertag (CompensationDayOff for_holiday) mindert wie bisher
 *    unabhängig von geleisteter Arbeit (Voll- bzw. Dreimonatslogik).
 *  - Krank (NOT_AVAILABLE) und Urlaub (OFF_WORK) lassen das Soll stehen.
 *
 * Ist:
 *  - Tag mit Tagesbuchung (nächtliche Buchung, work_time_bookings): gebuchtes Soll UND Ist dieser
 *    Zeile – die Anzeige entspricht damit immer dem Zeitkonto. Weicht die aktuelle Rechnung ab
 *    (rückwirkend Krank, Muster, Schichtänderung …), steht die Differenz in rebook_difference
 *    („Tag neu buchen“), sie wird nie stillschweigend angezeigt.
 *  - Sonst Schichtminuten (Pause ab dem ersten Schichttag, Rest geht auf den Folgetag über) plus Individualzeiten.
 *  - Manuelle/Korrekturbuchungen sind reine Saldo-Deltas und kommen in beiden Fällen zum Ist hinzu.
 *  - Krank/Urlaub sind soll-neutral: ganzer Tag -> Ist = Soll; Halbtag -> Arbeit + 0,5 · Soll.
 *    Bei unbekanntem Soll bleibt nur die tatsächliche Arbeit als Ist (kein Neutralanteil).
 *
 * Aggregate über Zeiträume (Woche/Monat/Spielzeit) über summarizeRange(): sobald ein Tag ohne
 * Muster im Zeitraum liegt, sind Soll und Differenz null und target_unknown = true.
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

    /**
     * Tagessoll in Minuten; null = kein gültiges Arbeitszeitmuster (Soll unbekannt).
     */
    public function targetMinutes(User|Freelancer|ServiceProvider $entity, Carbon $day, ?array $context = null): ?int
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
     * Optionen: use_bookings (bool, default true), legacy_adjustments (bool, default = use_bookings),
     * special_days (array 'Y-m-d' => Name), holiday_comp_days (iterable<CompensationDayOff> für diese Person).
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
     * Summen über Tages-Breakdowns (z. B. aus breakdownForRange). Soll/Differenz sind null, sobald
     * mindestens ein Tag ohne gültiges Muster enthalten ist (target_unknown), Ist wird immer summiert.
     *
     * @param iterable<array<string, mixed>> $breakdowns
     * @return array{
     *     target: int|null, actual: int, balance: int|null, target_unknown: bool,
     *     days_without_pattern: int, days: int, known_target: int
     * }
     */
    public static function summarizeRange(iterable $breakdowns): array
    {
        $target = 0;
        $actual = 0;
        $daysWithoutPattern = 0;
        $days = 0;

        foreach ($breakdowns as $day) {
            $days++;
            $actual += (int) ($day['actual'] ?? 0);
            if (($day['target'] ?? null) === null || !empty($day['target_unknown'])) {
                $daysWithoutPattern++;
                continue;
            }
            $target += (int) $day['target'];
        }

        $unknown = $daysWithoutPattern > 0;

        return [
            'target' => $unknown ? null : $target,
            'actual' => $actual,
            'balance' => $unknown ? null : $actual - $target,
            'target_unknown' => $unknown,
            'days_without_pattern' => $daysWithoutPattern,
            'days' => $days,
            'known_target' => $target,
        ];
    }

    /**
     * @return array{
     *     date: string, target: int|null, actual: int, base_target: int|null, balance: int|null,
     *     target_unknown: bool,
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

        $fullDayIndividual = !empty($context['individual_full_days'][$key]);
        $booking = $context['bookings'][$key] ?? null;
        $absence = $context['absences'][$key] ?? null;
        $sickFactor = (float) ($absence['sick_factor'] ?? 0.0);
        $vacationFactor = (float) ($absence['vacation_factor'] ?? 0.0);
        $neutralFactor = min(1.0, $sickFactor + $vacationFactor);

        $isSpecialDay = array_key_exists($key, $context['special_days'] ?? []);
        $specialDayName = $isSpecialDay ? ($context['special_days'][$key] ?? null) : null;
        // Vertragshistorie: der Sondertag-Schalter gilt je Tag – aus dem Kontext (buildContext), sonst
        // (fremder Kontext ohne den Schlüssel) über den SpecialDayService; nur an Sondertagen auflösen.
        $specialDayCounts = $isSpecialDay
            && $entity instanceof User
            && (
                $context['special_day_rule'][$key]
                ?? $this->specialDayService->specialDayRuleActiveFor($entity, $day)
            );
        $threeMonthMode = (bool) ($context['three_month_mode'] ?? false);

        $reduction = 0;
        $reason = null;
        $referencePeriod = null;
        $referenceAverage = null;

        if ($entity instanceof User && $baseTarget !== null && $baseTarget > 0) {
            if ($specialDayCounts) {
                // Nur Sondertage, an denen keine Arbeit BEGINNT, senken das Soll (wie die Regelprüfung, die dann
                // den Ersatzruhetag vorschlägt). Der Überhang einer Nachtschicht vom Vortag oder eine am Vortag
                // begonnene Zeit hebt die Minderung nicht auf; diese Minuten zählen als Plus.
                $workStartsToday = $fullDayIndividual || !empty($context['work_starts'][$key] ?? null)
                    || (!array_key_exists('work_starts', $context) && $workMinutes > 0);
                if (!$workStartsToday) {
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

        $liveTargetUnknown = $baseTarget === null;
        $liveTarget = $liveTargetUnknown ? null : max(0, $baseTarget - $reduction);

        if ($liveTargetUnknown) {
            // Ohne Soll ist der soll-neutrale Anteil (Krank/Urlaub) nicht bestimmbar: nur echte Arbeit
            $liveActual = $workMinutes;
        } elseif ($neutralFactor >= 1.0) {
            // Ganzer Tag abwesend: Ist = Soll. Bei reinem Urlaub kommt trotzdem geleistete Arbeit dazu; bei
            // Krankheit nicht – die geplante Schicht bleibt dort oft zugewiesen, bis es eine Vertretung gibt.
            $liveActual = $sickFactor > 0.0 ? $liveTarget : $liveTarget + $workMinutes;
        } elseif ($neutralFactor > 0.0) {
            $liveActual = $workMinutes + (int) round($liveTarget * $neutralFactor);
        } else {
            $liveActual = $workMinutes;
        }
        if ($fullDayIndividual && !$liveTargetUnknown) {
            // Ganztägige individuelle Zeit (Gastspiel, Reisetag): zählt das Tagessoll, mindestens
            $liveActual = max($liveActual, $liveTarget);
        }
        // Was die Tagesbuchung jetzt buchen würde (WorkTimeBookingService::bookDay rechnet genauso)
        $liveDailyBalance = $liveTargetUnknown ? null : $liveActual - $liveTarget;

        $isBooked = $booking !== null && !empty($booking['has_daily']);
        $extraChange = (int) ($booking['extra_change'] ?? 0);

        if ($isBooked) {
            // Gebuchter Tag: Anzeige = Zeitkonto (gebuchtes Soll/Ist + Zusatzbuchungen), nie Live-Werte –
            // sonst laufen Tagessalden und Kontostand nach rückwirkenden Änderungen auseinander. Soll aus
            // Ist − gebuchtem Saldo: Altzeilen mit Saldo ≠ Ist − Soll (frühere Krank-Logik) bleiben so in sich
            // stimmig (Tages- und Zeitraumsummen = Kontobewegung).
            $targetUnknown = false;
            $actual = (int) $booking['daily_worked'] + $extraChange;
            $balance = (int) $booking['daily_change'] + $extraChange;
            $target = (int) $booking['daily_worked'] - (int) $booking['daily_change'];
        } else {
            // Nicht (oder noch nicht) per Tagesbuchung gebucht: Live-Werte; manuelle/Korrekturbuchungen
            // kommen als Delta hinzu, ersetzen aber nicht Schichten und Krank/Urlaub.
            $targetUnknown = $liveTargetUnknown;
            $target = $liveTarget;
            $actual = $liveActual + $extraChange;
            $balance = $targetUnknown ? null : $actual - $target;
        }

        // Differenz zwischen aktueller Rechnung und Gebuchtem: „Tag neu buchen“ würde genau sie buchen. Alte
        // Korrekturzeilen aus Zeitänderungen (bis 10/2026 am Genehmigungstag gebucht) decken die Änderung dieses
        // Schichttags schon ab – abziehen, sonst würde sie ein zweites Mal gebucht (bookDay zieht sie genauso ab).
        $rebookDifference = $liveDailyBalance === null
            ? null
            : $liveDailyBalance - ($isBooked ? (int) $booking['daily_change'] : 0)
                - (int) ($context['legacy_adjustments'][$key] ?? 0);

        return [
            'date' => $key,
            'target' => $target,
            'actual' => $actual,
            'base_target' => $baseTarget,
            'balance' => $balance,
            'target_unknown' => $targetUnknown,
            'is_booked' => $isBooked,
            'booked_balance' => $booking !== null ? (int) $booking['balance_change'] : 0,
            'live_target' => $liveTarget,
            'live_actual' => $liveActual,
            'rebook_difference' => $rebookDifference,
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
        // Alte Korrekturzeilen auch ohne Buchungen: die Tagesbuchung (bookDay) zieht sie wie rebook_difference ab
        $useLegacyAdjustments = (bool) ($options['legacy_adjustments'] ?? $useBookings);
        $isUser = $entity instanceof User;

        $specialDays = $isUser
            ? ($options['special_days'] ?? $this->specialDayService->specialDaysBetween($start, $end))
            : [];

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'shift_minutes' => $this->shiftMinutesPerDay($entity, $start, $end),
            'individual_minutes' => $this->individualMinutesPerDay($entity, $start, $end),
            'individual_full_days' => $this->fullDayIndividualDays($entity, $start, $end),
            // Nur für Sondertage relevant (Minderung entfällt nur, wenn an dem Tag Arbeit BEGINNT)
            'work_starts' => $isUser && $specialDays !== [] ? $this->workStartsPerDay($entity, $start, $end) : [],
            'bookings' => $isUser && $useBookings ? $this->bookingsPerDay($entity, $start, $end) : [],
            'legacy_adjustments' => $isUser && $useLegacyAdjustments
                ? $this->legacyAdjustmentsPerShiftDay($entity)
                : [],
            'absences' => $this->absencesPerDay($entity, $start, $end),
            'special_days' => $specialDays,
            // Sondertag-Schalter je Sondertag aus der EINMAL geladenen Vertragshistorie (kein Query je Tag,
            // kein prozessweiter Resolver-Cache nötig → unkritisch unter Octane/Swoole)
            'special_day_rule' => $isUser ? $this->specialDayRulePerDay($entity, array_keys($specialDays)) : [],
            'three_month_mode' => $isUser && $this->threeMonthAverageTargetService->usesThreeMonthAverage($entity),
            'patterns' => $isUser ? $this->patternsPerDay($entity, $start, $end) : [],
            'holiday_comp' => $isUser
                ? $this->holidayCompensationPerDay($entity, $start, $end, $options['holiday_comp_days'] ?? null)
                : [],
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
        // Tagesgrenze exklusiv um 24:00, sonst fehlt bei Über-Mitternacht-Schichten die Minute 23:59.
        // Kalendertage über strtotime('+1 day') statt +86400: am Tag der Zeitumstellung hat der Tag
        // 23 bzw. 25 Stunden — mit festen 86400 s verschob sich das Tagesfenster und die Schicht
        // am Umstellungstag fiel aus der Zählung (Regelverstoß blieb unentdeckt).
        $rangeEndTimestamp = self::nextCalendarDay(strtotime($end->toDateString() . ' 00:00:00'));

        $dayTimestamps = [];
        $ts = $rangeStartTimestamp;
        while ($ts < $rangeEndTimestamp) {
            $dateStr = date('Y-m-d', $ts);
            $next = self::nextCalendarDay($ts);
            $shiftMinutesPerDay[$dateStr] = 0;
            $dayTimestamps[$dateStr] = [
                'start' => $ts,
                'end' => $next,
            ];
            $ts = $next;
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
            // Ende ≤ Beginn bei gleichem Enddatum (Datensatz ohne Folgetag, z. B. 22:00–02:00, oder 08:00–08:00 aus
            // dem früheren Schichtdialog = 24 h): Ende am Folgetag – wie Schichtvorlagen, Zeitänderungsantrag und
            // Arbeitszeitübersicht-Export, sonst zählte die Schicht 0 Minuten
            if ($shiftEndTs <= $shiftStartTs && $eDateOnly === $sDateOnly) {
                $shiftEndTs = strtotime("{$eDateOnly} {$eTime} +1 day");
            }
            if ($shiftEndTs <= $rangeStartTimestamp || $shiftStartTs >= $rangeEndTimestamp) {
                continue;
            }

            $breakMinutes = (int) ($shift->break_minutes ?? 0);
            $firstDayStr = date('Y-m-d', max($shiftStartTs, $rangeStartTimestamp));
            $lastDayStr = date('Y-m-d', min($shiftEndTs - 1, $rangeEndTimestamp - 1));
            // Pause ab dem ersten Tag der Schicht abziehen – auch wenn der erste Tag vor dem Zeitraum liegt
            $shiftFirstDayStr = date('Y-m-d', $shiftStartTs);

            // Ab dem ersten Schichttag laufen (auch vor dem Zeitraum), damit eine Pause, die länger ist als
            // der Anteil vor Mitternacht, auf den Folgetag übertragen wird statt verloren zu gehen.
            $dayTs = strtotime($shiftFirstDayStr);
            $lastDayTs = strtotime($lastDayStr);
            $remainingBreak = max(0, $breakMinutes);

            while ($dayTs <= $lastDayTs) {
                $dateStr = date('Y-m-d', $dayTs);
                $dayStartTimestamp = $dayTimestamps[$dateStr]['start'] ?? $dayTs;
                $dayEndTimestamp = $dayTimestamps[$dateStr]['end'] ?? self::nextCalendarDay($dayTs);

                $workStartTimestamp = max($shiftStartTs, $dayStartTimestamp);
                $workEndTimestamp = min($shiftEndTs, $dayEndTimestamp);

                if ($workStartTimestamp < $workEndTimestamp) {
                    $duration = (int) (($workEndTimestamp - $workStartTimestamp) / 60);
                    $deducted = min($remainingBreak, $duration);
                    $duration -= $deducted;
                    $remainingBreak -= $deducted;
                    if ($dateStr >= $firstDayStr) {
                        $shiftMinutesPerDay[$dateStr] = ($shiftMinutesPerDay[$dateStr] ?? 0) + $duration;
                    }
                }

                $dayTs = self::nextCalendarDay($dayTs);
            }
        }

        return $shiftMinutesPerDay;
    }

    /** 00:00 des folgenden Kalendertags (DST-sicher, statt Timestamp + 86400). */
    private static function nextCalendarDay(int $dayStartTimestamp): int
    {
        return (int) strtotime('+1 day', $dayStartTimestamp);
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
     * Nur das Basis-Tagessoll je Tag aus dem Muster, ohne Schichten/Buchungen zu laden.
     * null = an diesem Tag gilt kein Arbeitszeitmuster (Soll unbekannt).
     *
     * @return array<string, int|null> 'Y-m-d' => Minuten
     */
    public function baseTargetsForRange(User $user, Carbon $start, Carbon $end): array
    {
        $context = [
            'patterns' => $this->patternsPerDay($user, $start, $end),
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
     *
     * User: Minuten des am Tag gültigen Musters, null ohne Muster (Soll unbekannt – kein
     * Fallback auf weekly_working_hours). Externe haben kein Soll: 0.
     */
    public function baseTargetMinutes(
        User|Freelancer|ServiceProvider $entity,
        Carbon $day,
        ?array $context = null
    ): ?int {
        if (!$entity instanceof User) {
            return 0;
        }

        $key = $day->toDateString();
        $weekday = self::WEEKDAYS[$day->dayOfWeek];

        $patterns = $context !== null && array_key_exists('patterns', $context)
            ? $context['patterns']
            : $this->patternsPerDay($entity, $day, $day);
        $pattern = $patterns[$key] ?? null;
        if ($pattern instanceof UserWorkTime) {
            return self::patternDayMinutes($pattern, $weekday);
        }

        return null;
    }

    /**
     * Wochenstunden eines Musters (Summe der sieben Wochentage, Vorlagenreferenz berücksichtigt).
     */
    public static function weeklyPatternMinutes(UserWorkTime $workTime): int
    {
        $minutes = 0;
        foreach (self::WEEKDAYS as $weekday) {
            $minutes += self::patternDayMinutes($workTime, $weekday);
        }

        return $minutes;
    }

    /**
     * Wochenstunden laut dem am Stichtag (Default heute) gültigen Arbeitszeitmuster, z. B. 38.5;
     * null ohne gültiges Muster. Ersetzt die frühere Spalte users.weekly_working_hours.
     */
    public function currentWeeklyHours(User $user, ?Carbon $day = null): ?float
    {
        $day = ($day ?? Carbon::today())->startOfDay();
        $pattern = $this->patternForDate($user, $day);
        if ($pattern === null) {
            return null;
        }

        return round(self::weeklyPatternMinutes($pattern) / 60, 2);
    }

    /**
     * IDs der übergebenen User, für die am Stichtag ein Arbeitszeitmuster gilt – EINE Query für
     * alle IDs (Personalverwaltung: Warn-Badge "Arbeitszeitmuster fehlt" ohne N+1).
     *
     * @param array<int, int> $userIds
     * @return array<int, true> user_id => true
     */
    public function userIdsWithPatternOn(array $userIds, ?Carbon $day = null): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if ($userIds === []) {
            return [];
        }
        $dateKey = ($day ?? Carbon::today())->toDateString();

        $ids = UserWorkTime::query()
            ->whereIn('user_id', $userIds)
            ->where(function ($q) use ($dateKey): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $dateKey);
            })
            ->where(function ($q) use ($dateKey): void {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $dateKey);
            })
            ->distinct()
            ->pluck('user_id');

        $result = [];
        foreach ($ids as $id) {
            $result[(int) $id] = true;
        }

        return $result;
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

    /**
     * Sondertag-Schalter (special_day_rule_active, Zuweisung vor Vorlage des am Tag gültigen Zeitraums)
     * je Sondertag, 'Y-m-d' => bool. Die Historie wird einmal mitgeladen (contractAssigns.userContract);
     * die Auflösung je Tag läuft danach rein im Speicher (User::contractAssignFor auf der geladenen Relation).
     *
     * @param list<string> $dayKeys
     * @return array<string, bool>
     */
    private function specialDayRulePerDay(User $user, array $dayKeys): array
    {
        if ($dayKeys === []) {
            return [];
        }

        $user->loadMissing('contractAssigns.userContract');

        $result = [];
        foreach ($dayKeys as $dayKey) {
            $result[$dayKey] = $this->specialDayService->specialDayRuleActiveFor($user, Carbon::parse($dayKey));
        }

        return $result;
    }

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

        // Ende über Schicht ODER individuelle Zeit (Pivot), einen Tag Puffer: eine über Mitternacht verlängerte
        // Zeit endet erst am Folgetag, und Datensätze ohne Folgedatum (22:00–02:00 am selben Datum) enden
        // rechnerisch am Folgetag. Zugeschnitten wird danach exakt über die Uhrzeiten.
        $earliestEnd = $start->copy()->subDay()->toDateString();

        return $entity->shifts()
            ->where('shifts.start_date', '<=', $end->toDateString())
            ->where(function ($query) use ($earliestEnd): void {
                $query->where('shifts.end_date', '>=', $earliestEnd)
                    ->orWhere('shift_workers.end_date', '>=', $earliestEnd);
            })
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
            // Ein Tag Puffer: Zeiten über Mitternacht ohne Folgedatum enden rechnerisch erst am Folgetag
            : $entity->individualTimes()
                ->individualByDateRange($start->copy()->subDay()->toDateString(), $end->toDateString())
                ->get();

        $startKey = $start->toDateString();
        $endKey = $end->toDateString();
        $rangeStartTimestamp = strtotime($startKey . ' 00:00:00');
        // Tagesgrenze exklusiv um 24:00 (wie shiftMinutesPerDay)
        $rangeEndTimestamp = self::nextCalendarDay(strtotime($endKey . ' 00:00:00'));
        $result = [];

        foreach ($individualTimes as $individualTime) {
            if ((bool) ($individualTime->full_day ?? false)) {
                continue; // ganztägig = Tagessoll (fullDayIndividualDays), nicht working_time_minutes (1440)
            }
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
                // Ende ≤ Beginn ohne Folgedatum (z. B. Serie 22:00–04:00): Ende am Folgetag, wie bei Schichten
                if (
                    $timeStartTs !== false && $timeEndTs !== false && $timeEndTs <= $timeStartTs
                    && self::dateOnly($individualTime->end_date) === self::dateOnly($individualTime->start_date)
                ) {
                    $timeEndTs = strtotime(
                        self::dateOnly($individualTime->end_date) . ' '
                        . self::timeOnly($individualTime->end_time) . ' +1 day'
                    );
                }

                if ($timeStartTs !== false && $timeEndTs !== false && $timeEndTs > $timeStartTs) {
                    if ($timeEndTs <= $rangeStartTimestamp || $timeStartTs >= $rangeEndTimestamp) {
                        continue;
                    }

                    // Pause ab dem ersten Tag des Eintrags (auch vor dem Zeitraum) abziehen; was dort keinen
                    // Platz hat, geht auf den Folgetag über
                    $remainingBreak = max(0, (int) ($individualTime->break_minutes ?? 0));
                    $rangeFirstDay = date('Y-m-d', max($timeStartTs, $rangeStartTimestamp));
                    $dayTs = strtotime(date('Y-m-d', $timeStartTs));
                    $lastDayTs = strtotime(date('Y-m-d', min($timeEndTs - 1, $rangeEndTimestamp - 1)));

                    while ($dayTs <= $lastDayTs) {
                        $dateStr = date('Y-m-d', $dayTs);
                        $workStart = max($timeStartTs, $dayTs);
                        $workEnd = min($timeEndTs, self::nextCalendarDay($dayTs));
                        if ($workStart < $workEnd) {
                            $duration = intdiv($workEnd - $workStart, 60);
                            $deducted = min($remainingBreak, $duration);
                            $duration -= $deducted;
                            $remainingBreak -= $deducted;
                            if ($dateStr >= $rangeFirstDay) {
                                $result[$dateStr] = ($result[$dateStr] ?? 0) + $duration;
                            }
                        }
                        $dayTs = self::nextCalendarDay($dayTs);
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
     * Tageszeile (nächtliche Buchung, über den Namen) und Zusatzbuchungen (manuell/Korrektur) getrennt.
     *
     * @return array<string, array{
     *     worked: int, wanted: int, night: int, balance_change: int, is_special_day: bool,
     *     has_daily: bool, daily_worked: int, daily_wanted: int, daily_change: int, extra_change: int
     * }>
     */
    private function bookingsPerDay(User $user, Carbon $start, Carbon $end): array
    {
        $startKey = $start->toDateString();
        $endKey = $end->toDateString();

        $bookings = $user->relationLoaded('workTimeBookings')
            ? $user->workTimeBookings
            : $user->workTimeBookings()->whereBetween('booking_day', [$startKey, $endKey])->get();

        $result = [];
        // Nach id: bei Altdaten-Duplikaten ist die älteste Tageszeile „die“ Tagesbuchung (wie getPreviousBooking),
        // weitere Zeilen gleichen Namens zählen als Zusatzbuchung (sie stecken ja im Saldo)
        foreach ($bookings->sortBy('id') as $booking) {
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
                'has_daily' => false,
                'daily_worked' => 0,
                'daily_wanted' => 0,
                'daily_change' => 0,
                'extra_change' => 0,
            ];
            $change = (int) $booking->work_time_balance_change;
            $entry['worked'] += (int) $booking->worked_hours;
            $entry['wanted'] += (int) $booking->wanted_working_hours;
            $entry['night'] += (int) $booking->nightly_working_hours;
            $entry['balance_change'] += $change;
            $entry['is_special_day'] = $entry['is_special_day'] || (bool) $booking->is_special_day;
            if (
                !$entry['has_daily']
                && $booking->name === WorkTimeBookingRepository::dailyBookingName(Carbon::parse($dayKey))
            ) {
                $entry['has_daily'] = true;
                $entry['daily_worked'] = (int) $booking->worked_hours;
                $entry['daily_wanted'] = (int) $booking->wanted_working_hours;
                $entry['daily_change'] = $change;
            } else {
                // Manuelle Buchung / Korrektur: reines Saldo-Delta (Soll 0) – zählt als Ist-Zuschlag
                $entry['extra_change'] += $change;
            }
            $result[$dayKey] = $entry;
        }

        return $result;
    }

    /**
     * Tage mit ganztägiger individueller Zeit im Zeitraum ('Y-m-d' => true).
     *
     * @return array<string, true>
     */
    public function fullDayIndividualDays(User|Freelancer|ServiceProvider $entity, Carbon $start, Carbon $end): array
    {
        $startKey = $start->toDateString();
        $endKey = $end->toDateString();
        $individualTimes = $entity->relationLoaded('individualTimes')
            ? $entity->individualTimes
            : $entity->individualTimes()->individualByDateRange($startKey, $endKey)->get();

        $days = [];
        foreach ($individualTimes as $individualTime) {
            if (!(bool) ($individualTime->full_day ?? false)) {
                continue;
            }
            foreach (($individualTime->days_of_individual_time ?? []) as $day) {
                $dayKey = is_scalar($day) ? self::dateOnly((string) $day) : null;
                if ($dayKey !== null && $dayKey >= $startKey && $dayKey <= $endKey) {
                    $days[$dayKey] = true;
                }
            }
        }

        return $days;
    }

    /**
     * Tage, an denen eine Schicht (Pivot-Beginn) oder eine individuelle Zeit beginnt ('Y-m-d' => true).
     *
     * @return array<string, true>
     */
    private function workStartsPerDay(User $user, Carbon $start, Carbon $end): array
    {
        $startKey = $start->toDateString();
        $endKey = $end->toDateString();
        $days = [];

        foreach ($this->shiftsFor($user, $start, $end) as $shift) {
            $startDate = $shift->pivot?->start_date ?? $shift->start_date ?? null;
            $dayKey = $startDate !== null ? self::dateOnly($startDate) : null;
            if ($dayKey !== null && $dayKey >= $startKey && $dayKey <= $endKey) {
                $days[$dayKey] = true;
            }
        }

        $individualTimes = $user->relationLoaded('individualTimes')
            ? $user->individualTimes
            : $user->individualTimes()->individualByDateRange($startKey, $endKey)->get();
        foreach ($individualTimes as $individualTime) {
            $hasTimes = !empty($individualTime->start_time) && !empty($individualTime->start_date);
            $entryDays = $hasTimes
                ? [self::dateOnly($individualTime->start_date)]
                : array_map(
                    static fn ($day): string => self::dateOnly((string) $day),
                    $individualTime->days_of_individual_time ?? []
                );
            foreach ($entryDays as $dayKey) {
                if ($dayKey >= $startKey && $dayKey <= $endKey) {
                    $days[$dayKey] = true;
                }
            }
        }

        return $days;
    }

    /**
     * Alte Korrekturbuchungen aus genehmigten Zeitänderungen (Name adjustment_work_time_change_request_<shift>),
     * die bis 10/2026 am Genehmigungstag statt am Schichttag gebucht wurden: Summe je SCHICHTTAG der Person.
     *
     * @return array<string, int> 'Y-m-d' (Schichttag) => Minuten
     */
    private function legacyAdjustmentsPerShiftDay(User $user): array
    {
        $prefix = 'adjustment_work_time_change_request_';
        $adjustments = $user->workTimeBookings()
            ->where('name', 'like', 'adjustment\\_work\\_time\\_change\\_request\\_%')
            ->get(['name', 'work_time_balance_change']);
        if ($adjustments->isEmpty()) {
            return [];
        }

        $shiftIds = $adjustments
            ->map(fn ($booking): int => (int) substr((string) $booking->name, strlen($prefix)))
            ->filter()
            ->unique()
            ->values();
        $shiftDays = DB::table('shift_workers')
            ->join('shifts', 'shifts.id', '=', 'shift_workers.shift_id')
            ->where('shift_workers.employable_type', User::class)
            ->where('shift_workers.employable_id', $user->id)
            ->whereIn('shift_workers.shift_id', $shiftIds)
            ->selectRaw(
                'shift_workers.shift_id as shift_id, COALESCE(shift_workers.start_date, shifts.start_date) as shift_day'
            )
            ->pluck('shift_day', 'shift_id');

        $result = [];
        foreach ($adjustments as $adjustment) {
            $shiftDay = $shiftDays[(int) substr((string) $adjustment->name, strlen($prefix))] ?? null;
            if ($shiftDay === null) {
                continue;
            }
            $dayKey = self::dateOnly((string) $shiftDay);
            $result[$dayKey] = ($result[$dayKey] ?? 0) + (int) $adjustment->work_time_balance_change;
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
        // Rohwert ("08:00:00") statt datetime-Cast: der Cast setzt das heutige Datum ein und verschiebt am Tag der
        // Sommerzeitumstellung Werte zwischen 02:00 und 03:00 um eine Stunde
        $time = $workTime->getAttributes()[$weekday] ?? null;

        if ($time === null && $workTime->work_time_pattern_id) {
            // Zeile ohne eigene Zeiten = reine Referenz auf die Vorlage
            $hasOwnTimes = false;
            foreach (self::WEEKDAYS as $name) {
                if (($workTime->getAttributes()[$name] ?? null) !== null) {
                    $hasOwnTimes = true;
                    break;
                }
            }
            if (!$hasOwnTimes) {
                $time = $workTime->workTimePattern?->getAttributes()[$weekday] ?? null;
            }
        }

        if ($time === null) {
            return 0;
        }
        if ($time instanceof \DateTimeInterface) {
            return (int) $time->format('G') * 60 + (int) $time->format('i');
        }

        // "08:00", "08:00:00" oder (frisch gesetzter datetime-Cast) "2026-10-08 08:00:00": Uhrzeit am Ende
        if (preg_match('/(\d{1,2}):(\d{2})(?::\d{2})?$/', trim((string) $time), $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1] * 60 + (int) $matches[2];
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
