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
 *
 * Geprüft wird je Arbeitsintervall (Schicht mit effektiven Pivot-Zeiten bzw. individuelle Zeit) und je
 * Tag – ein Intervall wird dabei vollständig dem Tag zugerechnet, an dem es BEGINNT (wie
 * WorkTimeBookingService::calculateNightMinutes), auch wenn es über Mitternacht läuft:
 *  - Ein einzelnes Intervall mit mindestens 2 Stunden im Nachtfenster darf netto (Dauer minus Pause)
 *    höchstens Wert Stunden lang sein (20:00–06:00 = 10 h → Verstoß).
 *  - Mehrere Intervalle desselben Starttags zusammen: mindestens 2 Nachtstunden in Summe und mehr als
 *    Wert Stunden netto in Summe → Verstoß (deckt den Einzelfall mit ab, deshalb genau EIN Verstoß je Tag).
 * Wert = $rule->individual_number_value, leer = 8. Nachtminuten brutto (ohne Pausenabzug).
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
            $dayNightMinutes = 0;
            $dayNetMinutes = 0;
            $shift = null;

            // Intervalle, die an diesem Tag beginnen – über Mitternacht laufende zählen ganz zum Starttag.
            foreach ($this->getWorkIntervalsStartingOn($user, $date) as $interval) {
                $dayNightMinutes += $this->nightMinutesOfInterval($interval['start'], $interval['end'], $nightStart, $nightEnd);
                $dayNetMinutes += max(0, $interval['start']->diffInMinutes($interval['end']) - $interval['break_minutes']);
                $shift ??= $interval['shift'];
            }

            if ($dayNightMinutes < self::NIGHT_WORK_THRESHOLD_MINUTES) {
                continue;
            }

            $plannedHours = round($dayNetMinutes / 60.0, 2);
            if ($plannedHours <= $maxHours) {
                continue;
            }

            $data = [
                'type' => 'night_work_max_hours',
                'planned_hours' => $plannedHours,
                'night_hours' => round($dayNightMinutes / 60.0, 2),
                'max_allowed' => $maxHours,
                'night_window' => $nightStart . '–' . $nightEnd,
            ];
            $violations->push($shift
                ? $this->createViolation($rule, $shift, $user, $date, $data)
                : $this->createViolationWithoutShift($rule, $user, $date, $data));
        }

        return $violations;
    }

    /**
     * Minuten eines Arbeitsintervalls innerhalb des Nachtfensters – über alle berührten Kalendertage
     * (auch über Mitternacht), ohne Zuschnitt auf einen Tag.
     */
    private function nightMinutesOfInterval(Carbon $workStart, Carbon $workEnd, string $nightStart, string $nightEnd): int
    {
        if ($workStart->gte($workEnd)) {
            return 0;
        }

        $minutes = 0;
        $day = $workStart->copy()->startOfDay();
        $lastDay = $workEnd->copy()->startOfDay();
        while ($day->lte($lastDay)) {
            foreach ($this->nightSegmentsOfDay($day, $nightStart, $nightEnd) as [$segStart, $segEnd]) {
                $overlapStart = $workStart->greaterThan($segStart) ? $workStart : $segStart;
                $overlapEnd = $workEnd->lessThan($segEnd) ? $workEnd : $segEnd;
                if ($overlapStart->lt($overlapEnd)) {
                    $minutes += $overlapStart->diffInMinutes($overlapEnd);
                }
            }
            $day->addDay();
        }

        return $minutes;
    }

    /**
     * Nachtfenster-Abschnitte eines Kalendertags: ohne Mitternacht ein Abschnitt (z. B. 20:00–23:00),
     * über Mitternacht früher Morgen (00:00–Ende) und Abend (Beginn–24:00).
     *
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function nightSegmentsOfDay(Carbon $dayStart, string $nightStart, string $nightEnd): array
    {
        $startAt = $dayStart->copy()->setTimeFromTimeString($nightStart);
        $endAt = $dayStart->copy()->setTimeFromTimeString($nightEnd);
        if ($startAt->lt($endAt)) {
            return [[$startAt, $endAt]];
        }

        return [
            [$dayStart->copy(), $endAt],
            [$startAt, $dayStart->copy()->addDay()],
        ];
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
