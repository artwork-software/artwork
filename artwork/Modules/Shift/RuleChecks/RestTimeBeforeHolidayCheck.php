<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class RestTimeBeforeHolidayCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        // Extend date range by one day back to check previous day
        $extendedStartDate = $startDate->copy()->subDay();
        $dateRange = CarbonPeriod::create($extendedStartDate, $endDate);
        $latestShiftEndLastDay = null;

        foreach ($dateRange as $date) {
            // Check if it's a holiday (Sunday or in holiday database)
            if ($this->isHoliday($date)) {
                // Check rest time from previous day to first shift of current holiday
                $earliestShiftStart = $this->getEarliestShiftStartOfDay($user, $date);
                if ($latestShiftEndLastDay && $earliestShiftStart) {
                    $restHours = $this->calculateRestHours($latestShiftEndLastDay, $earliestShiftStart);
                    if ($restHours < $rule->individual_number_value) {
                        $shift = $this->getShiftForUserOnDate($user, $date);
                        if ($shift) {
                            $violations->push($this->createViolation($rule, $shift, $user, $date, [
                                'rest_hours' => $restHours,
                                'min_required' => $rule->individual_number_value
                            ]));
                        }
                    }
                }

                // Check rest time between multiple shifts on the same holiday
                $todayViolations = $this->checkRestTimeBetweenShiftsOnSameDay($rule, $user, $date);
                $violations = $violations->concat($todayViolations);
            }

            // Always update latestShiftEndLastDay for next iteration
            $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($user, $date);
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'restTimeBeforeHoliday';
    }


}