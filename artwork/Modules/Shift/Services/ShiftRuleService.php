<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ShiftRuleService
{
    public function validateRulesForUser(
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();
        
        // Get active contract for user
        $activeContract = $user->activeWorkContract();
        if (!$activeContract) {
            return $violations;
        }

        // Get rules assigned to this contract
        $rules = $this->getRulesForContract($activeContract);
        
        foreach ($rules as $rule) {
            $ruleViolations = $this->checkRuleForUser($rule, $user, $startDate, $endDate);
            $violations = $violations->concat($ruleViolations);
        }

        return $violations;
    }

    public function checkRuleForUser(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();

        switch ($rule->trigger_type) {
            case 'maxWorkingHoursOnDay':
                $violations = $this->checkMaxWorkingHoursOnDay($rule, $user, $startDate, $endDate);
                break;
            case 'maxConsecWorkingDays':
                $violations = $this->checkMaxConsecutiveWorkingDays($rule, $user, $startDate, $endDate);
                break;
            case 'weeklyMaxHours':
                $violations = $this->checkWeeklyMaxHours($rule, $user, $startDate, $endDate);
                break;
            case 'restTimeBeforeWorkday':
                $violations = $this->checkRestTimeBeforeWorkday($rule, $user, $startDate, $endDate);
                break;
            case 'restTimeBeforeHoliday':
                $violations = $this->checkRestTimeBeforeHoliday($rule, $user, $startDate, $endDate);
                break;
            case 'minDaysBeforeCommit':
                $violations = $this->checkMinDaysBeforeCommit($rule, $user, $startDate, $endDate);
                break;
        }

        return $violations;
    }

    private function checkMaxWorkingHoursOnDay(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        foreach ($dateRange as $date) {
            $plannedHours = $this->getPlannedWorkingHoursForDay($user, $date);
            
            if ($plannedHours > $rule->individual_number_value) {
                $shift = $this->getShiftForUserOnDate($user, $date);
                if ($shift) {
                    $violations->push($this->createViolation($rule, $shift, $user, $date, [
                        'planned_hours' => $plannedHours,
                        'max_allowed' => $rule->individual_number_value
                    ]));
                }
            }
        }

        return $violations;
    }

    private function checkMaxConsecutiveWorkingDays(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();
        $dateRange = CarbonPeriod::create($startDate, $endDate);
        $consecutiveDaysOfWork = 0;

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

    private function checkWeeklyMaxHours(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
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

    private function checkRestTimeBeforeWorkday(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();
        
        // Extend date range by one day back to check previous day
        $extendedStartDate = $startDate->copy()->subDay();
        $dateRange = CarbonPeriod::create($extendedStartDate, $endDate);
        $latestShiftEndLastDay = null;

        foreach ($dateRange as $date) {
            // Check if it's a workday (not Sunday and not holiday)
            if (!$this->isWorkday($date)) {
                $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($user, $date);
                continue;
            }

            $earliestShiftStart = $this->getEarliestShiftStartOfDay($user, $date);
            
            if ($latestShiftEndLastDay && $earliestShiftStart) {
                $restHours = $latestShiftEndLastDay->diffInHours($earliestShiftStart);
                
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

            $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($user, $date);
        }

        return $violations;
    }

    private function checkRestTimeBeforeHoliday(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();
        
        // Extend date range by one day back to check previous day
        $extendedStartDate = $startDate->copy()->subDay();
        $dateRange = CarbonPeriod::create($extendedStartDate, $endDate);
        $latestShiftEndLastDay = null;

        foreach ($dateRange as $date) {
            // Check if it's a holiday (Sunday or in holiday database)
            if (!$this->isHoliday($date)) {
                $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($user, $date);
                continue;
            }

            $earliestShiftStart = $this->getEarliestShiftStartOfDay($user, $date);
            
            if ($latestShiftEndLastDay && $earliestShiftStart) {
                $restHours = $latestShiftEndLastDay->diffInHours($earliestShiftStart);
                
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

            $latestShiftEndLastDay = $this->getLatestShiftEndOfDay($user, $date);
        }

        return $violations;
    }

    private function checkMinDaysBeforeCommit(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();
        $today = now();
        $futureDate = $today->copy()->addDays($rule->individual_number_value);

        // Get all non-committed shifts within the rule's time frame
        $shifts = Shift::where('is_committed', false)
            ->whereBetween('start_date', [$today, $futureDate])
            ->get();

        foreach ($shifts as $shift) {
            $violations->push($this->createViolation($rule, $shift, $user, Carbon::parse($shift->start_date), [
                'days_until_shift' => $today->diffInDays(Carbon::parse($shift->start_date)),
                'min_required' => $rule->individual_number_value
            ]));
        }

        return $violations;
    }

    private function getRulesForContract(UserContract $contract): Collection
    {
        return ShiftRule::whereHas('contracts', function ($query) use ($contract) {
            $query->where('contract_id', $contract->id);
        })->where('is_active', true)->get();
    }

    private function getPlannedWorkingHoursForDay(User $user, Carbon $date): float
    {
        $shifts = Shift::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->whereDate('start_date', '<=', $date)
        ->whereDate('end_date', '>=', $date)
        ->get();

        $totalHours = 0;
        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->start);
            $end = Carbon::parse($shift->end);
            $breakMinutes = $shift->break_minutes ?? 0;
            
            $totalMinutes = $start->diffInMinutes($end) - $breakMinutes;
            $totalHours += $totalMinutes / 60;
        }

        return $totalHours;
    }

    private function getShiftForUserOnDate(User $user, Carbon $date): ?Shift
    {
        return Shift::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->whereDate('start_date', '<=', $date)
        ->whereDate('end_date', '>=', $date)
        ->first();
    }

    private function getEarliestShiftStartOfDay(User $user, Carbon $date): ?Carbon
    {
        $shift = Shift::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->whereDate('start_date', $date)
        ->orderBy('start')
        ->first();

        return $shift ? Carbon::parse($date->format('Y-m-d') . ' ' . $shift->start) : null;
    }

    private function getLatestShiftEndOfDay(User $user, Carbon $date): ?Carbon
    {
        $shift = Shift::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->whereDate('end_date', $date)
        ->orderByDesc('end')
        ->first();

        return $shift ? Carbon::parse($date->format('Y-m-d') . ' ' . $shift->end) : null;
    }

    private function isWorkday(Carbon $date): bool
    {
        return !$date->isSunday() && !$this->isHoliday($date);
    }

    private function isHoliday(Carbon $date): bool
    {
        // Check if it's Sunday or check against holiday database
        if ($date->isSunday()) {
            return true;
        }

        // TODO: Check against actual holiday database table
        // For now, just return false for non-Sundays
        return false;
    }

    private function createViolation(
        ShiftRule $rule,
        Shift $shift,
        User $user,
        Carbon $date,
        array $violationData
    ): ShiftRuleViolation {
        // Check if violation already exists for this combination
        $existingViolation = ShiftRuleViolation::where([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'violation_date' => $date->format('Y-m-d')
        ])->first();

        if ($existingViolation) {
            // Update existing violation data if needed
            $existingViolation->update([
                'violation_data' => $violationData,
                'severity' => 'warning',
                'status' => 'active'
            ]);
            return $existingViolation;
        }

        // Create new violation - this will trigger the workflow automatically
        return ShiftRuleViolation::create([
            'shift_rule_id' => $rule->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'violation_date' => $date,
            'violation_data' => $violationData,
            'severity' => 'warning',
            'status' => 'active'
        ]);
    }

    public function validateShiftRulesForDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();
        
        // Get all users with active contracts
        $users = User::whereHas('contract')->get();
        
        foreach ($users as $user) {
            $userViolations = $this->validateRulesForUser($user, $startDate, $endDate);
            $violations = $violations->concat($userViolations);
        }

        return $violations;
    }
}