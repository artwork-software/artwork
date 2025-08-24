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

            $plannedWorkingHoursOfWeek += $this->getPlannedWorkingHoursForDay($user, $date);

            if ($plannedWorkingHoursOfWeek > $rule->individual_number_value) {
                $shift = $this->getShiftForUserOnDate($user, $date);
                if ($shift) {
                    $violations->push($this->createViolation($rule, $shift, $user, $date, [
                        'weekly_hours' => $plannedWorkingHoursOfWeek,
                        'max_allowed' => $rule->individual_number_value
                    ]));
                }
            }
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'weeklyMaxHours';
    }

}