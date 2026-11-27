<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Support\ShiftRuleConfig;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Höchstarbeitszeit in der anderen Tageshälfte eines halben freien Tages (NV Bühne: höchstens 4
 * Stunden, bei Endproben und Aufführungen höchstens 5).
 *
 * Geprüft werden Tage, an denen der Person GENAU EINE Tageshälfte als halber Ersatzfreitag gewährt
 * ist. $rule->individual_number_value = Höchststunden in der anderen Tageshälfte.
 *
 * Zusatzparameter (ShiftRuleConfig):
 *  - boundary: Uhrzeit, die Vormittag und Nachmittag trennt (Standard 14:00).
 *  - extended_hours / extended_shift_group_ids: Liegt in der Arbeitshälfte mindestens eine Schicht
 *    einer dieser Schichtgruppen (z. B. Endprobe, Vorstellung), gilt der erweiterte Wert.
 *
 * Gezählt wird die effektive Arbeitszeit der Person (Pivot-Zeit vor Schichtzeit, plus individuelle
 * Zeiten), zugeschnitten auf die Arbeitshälfte: bei freiem Vormittag ab der Grenze, bei freiem
 * Nachmittag bis zur Grenze. Die Pause wird abgezogen, wenn der Einsatz vollständig in der
 * Arbeitshälfte liegt. Ob ein Dienst in die freie Hälfte hineinreicht, prüft halfDayOffConflict.
 */
class HalfDayOffMaxHoursCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $halvesByDate = $this->getGrantedHalvesByDate($user, $startDate, $endDate);
        if ($halvesByDate->isEmpty()) {
            return $violations;
        }

        $boundaryTime = (string) ShiftRuleConfig::get($rule, 'boundary');
        $extendedHours = ShiftRuleConfig::get($rule, 'extended_hours');
        $extendedGroupIds = (array) ShiftRuleConfig::get($rule, 'extended_shift_group_ids');
        $baseLimit = (float) $rule->individual_number_value;

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $halves = $halvesByDate->get($date->format('Y-m-d')) ?? collect();
            $hasMorning = $halves->contains(fn ($half): bool => $half->half_day_period === 'morning');
            $hasAfternoon = $halves->contains(fn ($half): bool => $half->half_day_period === 'afternoon');
            if ($hasMorning === $hasAfternoon) {
                // Kein halber freier Tag oder beide Hälften frei (ganzer Tag, siehe halfDayOffConflict).
                continue;
            }

            $dayStart = $date->copy()->startOfDay();
            $boundary = $dayStart->copy()->setTimeFromTimeString($boundaryTime);
            // Freier Vormittag: gearbeitet wird ab der Grenze (inkl. Anteil nach Mitternacht).
            // Freier Nachmittag: gearbeitet wird bis zur Grenze.
            $windowStart = $hasMorning ? $boundary : $dayStart;
            $windowEnd = $hasMorning ? null : $boundary;

            $minutes = 0;
            $firstShift = null;
            $extended = false;
            foreach ($this->getWorkIntervals($user, $date, $date) as $interval) {
                if ($hasMorning && $interval['start_key'] !== $dayStart->toDateString()) {
                    // Einsatz vom Vortag: gehört nicht zur Arbeitshälfte dieses Tages.
                    continue;
                }
                $from = $interval['start']->greaterThan($windowStart) ? $interval['start'] : $windowStart;
                $to = $windowEnd !== null && $interval['end']->greaterThan($windowEnd)
                    ? $windowEnd
                    : $interval['end'];
                if ($to->lessThanOrEqualTo($from)) {
                    continue;
                }

                $clipped = (int) $from->diffInMinutes($to);
                $whole = (int) $interval['start']->diffInMinutes($interval['end']);
                if ($clipped === $whole) {
                    $clipped = max(0, $clipped - (int) $interval['break_minutes']);
                }
                $minutes += $clipped;

                if ($interval['shift'] !== null) {
                    $firstShift ??= $interval['shift'];
                    if (in_array((int) $interval['group_id'], $extendedGroupIds, true)) {
                        $extended = true;
                    }
                }
            }

            $limit = $extended && $extendedHours !== null ? (float) $extendedHours : $baseLimit;
            $hours = $minutes / 60.0;
            if ($limit <= 0 || $hours <= $limit) {
                continue;
            }

            $data = [
                'type' => 'half_day_off_max_hours',
                'half_day_period' => $hasMorning ? 'morning' : 'afternoon',
                'planned_hours' => round($hours, 2),
                'max_allowed' => $limit,
                'extended' => $extended && $extendedHours !== null,
                'boundary' => $boundaryTime,
            ];

            $violations->push(
                $firstShift !== null
                    ? $this->createViolation($rule, $firstShift, $user, $date, $data)
                    : $this->createViolationWithoutShift($rule, $user, $date, $data)
            );
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'halfDayOffMaxHours';
    }
}
