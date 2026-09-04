<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class WeeklyMaxHoursCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        // Extend date range to full weeks
        $startOfWeek = $startDate->isMonday() ? $startDate->copy() : $startDate->copy()->startOfWeek();
        $endOfWeek = $endDate->isSunday() ? $endDate->copy() : $endDate->copy()->endOfWeek();

        $dateRange = CarbonPeriod::create($startOfWeek, $endOfWeek);
        $plannedWorkingHoursOfWeek = 0;

        foreach ($dateRange as $date) {
            if ($date->isMonday()) {
                $plannedWorkingHoursOfWeek = 0;
            }

            $dayHours = $this->getPlannedWorkingHoursForDay($user, $date);
            $plannedWorkingHoursOfWeek += $dayHours;

            // Nur Tage mit geplanter Arbeit (Schicht oder individuelle Zeit) melden, nicht arbeitsfreie Folgetage.
            if ($dayHours > 0 && $plannedWorkingHoursOfWeek > $rule->individual_number_value) {
                $data = [
                    'weekly_hours' => $plannedWorkingHoursOfWeek,
                    'max_allowed' => $rule->individual_number_value,
                ];
                $shift = $this->getShiftForUserOnDate($user, $date);
                // Auch ohne Schicht (nur individuelle Zeiten) ist die Ueberschreitung ein Verstoss.
                $violations->push($shift
                    ? $this->createViolation($rule, $shift, $user, $date, $data)
                    : $this->createViolationWithoutShift($rule, $user, $date, $data));
            }
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'weeklyMaxHours';
    }

}