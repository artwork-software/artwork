<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Nachtarbeit-Tagesmaximum (ArbZG § 6 Abs. 2, vereinfacht).
 *
 * Nachtfenster = GeneralSettings start_night_time–end_night_time (Standard 22:00–06:00, über Mitternacht).
 * Ein Kalendertag gilt als Nachtarbeitstag, wenn die Person an diesem Tag mindestens 2 Stunden innerhalb
 * des Nachtfensters arbeitet (Fenster auf den Kalendertag zugeschnitten: 00:00–06:00 und 22:00–24:00).
 * An solchen Tagen darf die gesamte geplante Arbeit (Netto wie MaxWorkingHoursOnDayCheck: Pause am
 * ersten Schichttag, Tagesgrenze) höchstens Wert Stunden betragen ($rule->individual_number_value,
 * leer = 8). Die Nachtminuten werden brutto (ohne Pausenabzug) gezählt.
 */
class NightWorkMaxHoursCheck extends AbstractRuleCheck
{
    public const DEFAULT_MAX_HOURS = 8.0;
    public const NIGHT_WORK_THRESHOLD_MINUTES = 120;
    public const DEFAULT_NIGHT_START = '22:00';
    public const DEFAULT_NIGHT_END = '06:00';

    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $maxHours = $this->maxHoursFor($rule);
        [$nightStart, $nightEnd] = $this->nightWindow();

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $nightMinutes = $this->nightMinutesOnDay($user, $date, $nightStart, $nightEnd);
            if ($nightMinutes < self::NIGHT_WORK_THRESHOLD_MINUTES) {
                continue;
            }

            $plannedHours = $this->getPlannedWorkingHoursForDay($user, $date);
            if ($plannedHours <= $maxHours) {
                continue;
            }

            $data = [
                'type' => 'night_work_max_hours',
                'planned_hours' => $plannedHours,
                'night_hours' => round($nightMinutes / 60.0, 2),
                'max_allowed' => $maxHours,
                'night_window' => $nightStart . '–' . $nightEnd,
            ];
            $shift = $this->getShiftForUserOnDate($user, $date);
            $violations->push($shift
                ? $this->createViolation($rule, $shift, $user, $date, $data)
                : $this->createViolationWithoutShift($rule, $user, $date, $data));
        }

        return $violations;
    }

    /**
     * Minuten der Person innerhalb des Nachtfensters am Kalendertag (alle Intervalle, die den Tag berühren,
     * auf den Tag zugeschnitten).
     */
    private function nightMinutesOnDay(User $user, Carbon $date, string $nightStart, string $nightEnd): int
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        $segments = [];
        $startAt = $dayStart->copy()->setTimeFromTimeString($nightStart);
        $endAt = $dayStart->copy()->setTimeFromTimeString($nightEnd);
        if ($startAt->lt($endAt)) {
            // Fenster ohne Mitternacht (z. B. 20:00–23:00)
            $segments[] = [$startAt, $endAt];
        } else {
            // Über Mitternacht: früher Morgen des Tages + Abend des Tages
            $segments[] = [$dayStart->copy(), $endAt];
            $segments[] = [$startAt, $dayEnd->copy()];
        }

        $minutes = 0;
        foreach ($this->getWorkIntervals($user, $date, $date) as $interval) {
            $workStart = $interval['start']->greaterThan($dayStart) ? $interval['start'] : $dayStart;
            $workEnd = $interval['end']->lessThan($dayEnd) ? $interval['end'] : $dayEnd;
            if ($workStart->gte($workEnd)) {
                continue;
            }
            foreach ($segments as [$segStart, $segEnd]) {
                $overlapStart = $workStart->greaterThan($segStart) ? $workStart : $segStart;
                $overlapEnd = $workEnd->lessThan($segEnd) ? $workEnd : $segEnd;
                if ($overlapStart->lt($overlapEnd)) {
                    $minutes += $overlapStart->diffInMinutes($overlapEnd);
                }
            }
        }

        return $minutes;
    }

    /**
     * @return array{0: string, 1: string} ['HH:MM', 'HH:MM']
     */
    private function nightWindow(): array
    {
        $settings = app(GeneralSettings::class);
        $start = substr((string) ($settings->start_night_time ?? ''), 0, 5);
        $end = substr((string) ($settings->end_night_time ?? ''), 0, 5);

        return [
            preg_match('/^\d{2}:\d{2}$/', $start) ? $start : self::DEFAULT_NIGHT_START,
            preg_match('/^\d{2}:\d{2}$/', $end) ? $end : self::DEFAULT_NIGHT_END,
        ];
    }

    private function maxHoursFor(ShiftRule $rule): float
    {
        $value = (float) $rule->individual_number_value;

        return $value > 0 ? $value : self::DEFAULT_MAX_HOURS;
    }

    public function getTriggerType(): string
    {
        return 'nightWorkMaxHours';
    }
}
