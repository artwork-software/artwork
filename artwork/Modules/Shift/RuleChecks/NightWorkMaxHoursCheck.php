<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Support\NightWindow;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Nachtarbeit-Tagesmaximum (ArbZG § 6 Abs. 2, vereinfacht).
 *
 * Nachtfenster = GeneralSettings start_night_time–end_night_time (Standard 22:00–06:00, über Mitternacht);
 * Nachtminuten rechnet NightWindow::minutesWithin() — dieselbe Basis wie die nächtliche Buchung
 * (WorkTimeBookingService::calculateNightMinutes). Auch die Zuordnung zum Tag ist identisch:
 *  - Schichten (effektive Pivot-Zeiten) zählen VOLLSTÄNDIG zu dem Tag, an dem sie BEGINNEN — auch der
 *    Anteil nach Mitternacht (20:00–06:00 = 10 h netto, 8 h Nacht am Starttag).
 *  - Individuelle Zeiten werden je KALENDERTAG zugeschnitten: Tag D zählt den Anteil 00:00–24:00 von D,
 *    also auch den Ausläufer einer am Vortag begonnenen Zeit (23:00–03:00 → 1 h Nacht an D, 3 h an D+1).
 *    Nachtminuten nur mit Beginn- UND Endzeit (ganztägige Einträge zählen keine Nacht, wie die Buchung);
 *    die Pause wird am ersten Tag des Eintrags abgezogen.
 * Je Tag: mindestens 2 Nachtstunden in Summe und mehr als Wert Stunden netto in Summe → genau EIN
 * Verstoß (deckt den Einzelfall einer langen Nachtschicht mit ab).
 * Wert = $rule->individual_number_value, leer = 8. Nachtminuten brutto (ohne Pausenabzug).
 */
class NightWorkMaxHoursCheck extends AbstractRuleCheck
{
    public const DEFAULT_MAX_HOURS = 8.0;
    public const NIGHT_WORK_THRESHOLD_MINUTES = 120;
    public const DEFAULT_NIGHT_START = NightWindow::DEFAULT_START;
    public const DEFAULT_NIGHT_END = NightWindow::DEFAULT_END;

    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $maxHours = $this->maxHoursFor($rule);
        $window = NightWindow::fromSettings();

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dayNightMinutes = 0;
            $dayNetMinutes = 0;
            $shift = null;

            // Schichten, die an diesem Tag beginnen – über Mitternacht laufende zählen ganz zum Starttag.
            foreach ($this->getWorkIntervalsStartingOn($user, $date, false) as $interval) {
                $dayNightMinutes += $window->minutesWithin($interval['start'], $interval['end']);
                $dayNetMinutes += max(0, (int) $interval['start']->diffInMinutes($interval['end']) - $interval['break_minutes']);
                $shift ??= $interval['shift'];
            }

            // Individuelle Zeiten: Anteil dieses Kalendertags (auch der Ausläufer vom Vortag).
            foreach ($this->getIndividualTimeSegmentsOfDay($user, $date) as $segment) {
                $it = $segment['individual_time'];
                if ($it->start_time && $it->end_time && !$it->full_day) {
                    $dayNightMinutes += $window->minutesWithin($segment['start'], $segment['end']);
                }
                $dayNetMinutes += max(0, (int) $segment['start']->diffInMinutes($segment['end']) - $segment['break_minutes']);
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
                'night_window' => $window->label(),
            ];
            $violations->push($shift
                ? $this->createViolation($rule, $shift, $user, $date, $data)
                : $this->createViolationWithoutShift($rule, $user, $date, $data));
        }

        return $violations;
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
