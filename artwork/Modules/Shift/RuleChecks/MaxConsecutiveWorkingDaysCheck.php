<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class MaxConsecutiveWorkingDaysCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        // Look back before startDate to count any existing consecutive work streak
        $consecutiveDaysOfWork = 0;
        $checkDate = $startDate->copy()->subDay();
        while ($this->getPlannedWorkingHoursForDay($user, $checkDate) > 0) {
            $consecutiveDaysOfWork++;
            $checkDate->subDay();
        }

        foreach ($dateRange as $date) {
            $plannedHours = $this->getPlannedWorkingHoursForDay($user, $date);

            if ($plannedHours > 0) {
                $consecutiveDaysOfWork++;
            } else {
                $consecutiveDaysOfWork = 0;
            }

            if ($consecutiveDaysOfWork > $rule->individual_number_value) {
                $shift = $this->getShiftForUserOnDate($user, $date);
                if ($shift) {
                    $violations->push($this->createViolation($rule, $shift, $user, $date, [
                        'consecutive_days' => $consecutiveDaysOfWork,
                        'max_allowed' => $rule->individual_number_value
                    ]));
                }
            }
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'maxConsecWorkingDays';
    }

}