<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Support\ShiftRuleConfig;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Dienstfrei an einem festen Kalendertag nach Arbeit an einem anderen (NV Bühne: wer am 24. Dezember
 * arbeitet, hat am folgenden 1. Mai dienstfrei).
 *
 * Zusatzparameter (ShiftRuleConfig): worked und free, jeweils "MM-DD". Für jeden Tag "free" im
 * Prüfzeitraum wird der letzte davor liegende Tag "worked" betrachtet. War die Person dort
 * eingeplant (Schicht oder individuelle Zeit) und ist sie es auch am Tag "free", entsteht ein Verstoß
 * am Tag "free". $rule->individual_number_value wird nicht verwendet.
 */
class FreeDayAfterWorkOnDateCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $worked = ShiftRuleConfig::get($rule, 'worked');
        $free = ShiftRuleConfig::get($rule, 'free');
        if (!is_string($worked) || !is_string($free)) {
            return $violations;
        }

        for ($year = $startDate->year; $year <= $endDate->year; $year++) {
            $freeDay = $this->dateFor($year, $free);
            if ($freeDay === null || $freeDay->lt($startDate->copy()->startOfDay()) || $freeDay->gt($endDate)) {
                continue;
            }

            $workedDay = $this->dateFor($year, $worked);
            if ($workedDay === null || $workedDay->gte($freeDay)) {
                $workedDay = $this->dateFor($year - 1, $worked);
            }
            if ($workedDay === null) {
                continue;
            }

            if ($this->getOccupiedDayKeys($user, $workedDay, $workedDay) === []) {
                continue;
            }
            if ($this->getOccupiedDayKeys($user, $freeDay, $freeDay) === []) {
                continue;
            }

            $data = [
                'type' => 'free_day_after_work_on_date',
                'worked_date' => $workedDay->toDateString(),
                'free_date' => $freeDay->toDateString(),
            ];
            $shift = $this->getShiftForUserOnDate($user, $freeDay);
            $violations->push(
                $shift !== null
                    ? $this->createViolation($rule, $shift, $user, $freeDay, $data)
                    : $this->createViolationWithoutShift($rule, $user, $freeDay, $data)
            );
        }

        return $violations;
    }

    /**
     * Kalendertag "MM-DD" im Jahr, null wenn es ihn dort nicht gibt (29. Februar).
     */
    private function dateFor(int $year, string $monthDay): ?Carbon
    {
        [$month, $day] = array_map('intval', explode('-', $monthDay));

        return checkdate($month, $day, $year) ? Carbon::create($year, $month, $day)->startOfDay() : null;
    }

    public function getTriggerType(): string
    {
        return 'freeDayAfterWorkOnDate';
    }
}
