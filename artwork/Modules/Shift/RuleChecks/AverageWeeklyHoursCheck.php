<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Durchschnittliche Wochenstunden über einen Ausgleichszeitraum (ArbZG § 3, TVöD § 6 Abs. 2).
 *
 * Wert = maximaler Durchschnitt in Stunden ($rule->individual_number_value, leer = 48),
 * Zeitraum = $rule->period_weeks Wochen (leer = 24).
 *
 * Für jede Kalenderwoche (Mo–So) im Prüfzeitraum wird das rollierende Fenster der letzten period_weeks
 * Wochen (inkl. der Woche selbst) betrachtet: Summe der geplanten Netto-Minuten (Schichten mit
 * Pivot-Zeiten + individuelle Zeiten, wie WeeklyMaxHoursCheck) geteilt durch period_weeks. Liegt der
 * Durchschnitt über dem Wert, entsteht EIN Verstoß je Woche — an der letzten Schicht der Woche (ohne
 * Schicht, nur individuelle Zeiten: ohne Schichtbezug am letzten Arbeitstag der Woche).
 *
 * Gemeldet werden nur Wochen, deren Fenster mindestens zur Hälfte Daten hat (Wochen mit geplanter
 * Arbeit >= period_weeks / 2); Wochen ohne eigene Arbeit werden nicht gemeldet.
 */
class AverageWeeklyHoursCheck extends AbstractRuleCheck
{
    public const DEFAULT_MAX_AVERAGE_HOURS = 48.0;
    public const DEFAULT_PERIOD_WEEKS = 24;

    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $maxAverage = $this->maxAverageFor($rule);
        $periodWeeks = self::periodWeeksFor($rule);

        $firstMonday = $startDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $lastSunday = $endDate->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $windowStart = $firstMonday->copy()->subWeeks($periodWeeks - 1);

        // Minuten je Tag einmal für den gesamten Rückblick laden (kein Query je Tag).
        $minutesPerDay = $this->getPlannedMinutesPerDay($user, $windowStart, $lastSunday);

        // Wochenminuten, Schlüssel = Montag der Woche
        $minutesPerWeek = [];
        foreach ($minutesPerDay as $dayKey => $minutes) {
            $weekKey = Carbon::parse($dayKey)->startOfWeek(Carbon::MONDAY)->toDateString();
            $minutesPerWeek[$weekKey] = ($minutesPerWeek[$weekKey] ?? 0) + $minutes;
        }

        $minWeeksWithData = (int) ceil($periodWeeks / 2);

        for ($monday = $firstMonday->copy(); $monday->lte($lastSunday); $monday->addWeek()) {
            $weekKey = $monday->toDateString();
            $ownMinutes = (int) ($minutesPerWeek[$weekKey] ?? 0);
            if ($ownMinutes <= 0) {
                continue;
            }

            $total = 0;
            $weeksWithData = 0;
            for ($i = 0; $i < $periodWeeks; $i++) {
                $minutes = (int) ($minutesPerWeek[$monday->copy()->subWeeks($i)->toDateString()] ?? 0);
                $total += $minutes;
                if ($minutes > 0) {
                    $weeksWithData++;
                }
            }
            if ($weeksWithData < $minWeeksWithData) {
                continue;
            }

            $average = $total / 60.0 / $periodWeeks;
            if ($average <= $maxAverage) {
                continue;
            }

            $sunday = $monday->copy()->addDays(6);
            $data = [
                'type' => 'average_weekly_hours',
                'average_hours' => round($average, 2),
                'max_allowed' => $maxAverage,
                'period_weeks' => $periodWeeks,
                'total_hours' => round($total / 60.0, 2),
                'weeks_with_data' => $weeksWithData,
                'window_start' => $monday->copy()->subWeeks($periodWeeks - 1)->toDateString(),
                'window_end' => $sunday->toDateString(),
                'week_start' => $weekKey,
                'week_end' => $sunday->toDateString(),
            ];

            [$shift, $date] = $this->lastWorkOfWeek($user, $monday, $sunday, $minutesPerDay);
            $violations->push($shift !== null
                ? $this->createViolation($rule, $shift, $user, $date, $data)
                : $this->createViolationWithoutShift($rule, $user, $date, $data));
        }

        return $violations;
    }

    /**
     * Die Verstöße liegen in den vollen Kalenderwochen, die der Prüfzeitraum berührt.
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return [
            $startDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
            $endDate->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
        ];
    }

    public static function periodWeeksFor(ShiftRule $rule): int
    {
        $weeks = (int) ($rule->period_weeks ?? 0);

        return $weeks >= 2 ? $weeks : self::DEFAULT_PERIOD_WEEKS;
    }

    private function maxAverageFor(ShiftRule $rule): float
    {
        $value = (float) $rule->individual_number_value;

        return $value > 0 ? $value : self::DEFAULT_MAX_AVERAGE_HOURS;
    }

    /**
     * Letzte Schicht der Woche (effektiver Starttag) und ihr Datum; ohne Schicht der letzte Tag der Woche
     * mit geplanter Arbeit (nur individuelle Zeiten).
     *
     * @param array<string, int> $minutesPerDay
     * @return array{0: \Artwork\Modules\Shift\Models\Shift|null, 1: Carbon}
     */
    private function lastWorkOfWeek(User $user, Carbon $monday, Carbon $sunday, array $minutesPerDay): array
    {
        $lastShift = null;
        $lastShiftDate = null;
        foreach ($this->getWorkIntervals($user, $monday, $sunday, false) as $interval) {
            if ($interval['start_key'] < $monday->toDateString() || $interval['start_key'] > $sunday->toDateString()) {
                continue;
            }
            if ($lastShiftDate === null || $interval['start_key'] >= $lastShiftDate) {
                $lastShift = $interval['shift'];
                $lastShiftDate = $interval['start_key'];
            }
        }
        if ($lastShift !== null) {
            return [$lastShift, Carbon::parse($lastShiftDate)];
        }

        for ($day = $sunday->copy(); $day->gte($monday); $day->subDay()) {
            if (($minutesPerDay[$day->toDateString()] ?? 0) > 0) {
                return [null, $day];
            }
        }

        return [null, $sunday->copy()];
    }

    public function getTriggerType(): string
    {
        return 'averageWeeklyHours';
    }
}
