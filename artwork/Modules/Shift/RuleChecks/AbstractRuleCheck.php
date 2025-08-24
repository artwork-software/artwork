<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Shift\Contracts\ShiftRuleCheckInterface;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

abstract class AbstractRuleCheck implements ShiftRuleCheckInterface
{
    protected function getPlannedWorkingHoursForDay(User $user, Carbon $date): float
    {
        $shifts = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        $totalHours = 0;
        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->start_date)->setTimeFromTimeString($shift->start);
            $end = Carbon::parse($shift->end_date)->setTimeFromTimeString($shift->end);
            $breakMinutes = $shift->break_minutes ?? 0;

            $totalMinutes = $start->diffInMinutes($end) - $breakMinutes;
            $totalHours += $totalMinutes / 60;
        }

        return $totalHours;
    }

    protected function getShiftForUserOnDate(User $user, Carbon $date): ?Shift
    {
        return Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    protected function createViolation(
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
            'violation_date' => $date->format('Y-m-d'),
            'violation_data' => $violationData,
            'severity' => 'warning',
            'status' => 'active'
        ]);
    }

    protected function checkRestTimeBetweenShiftsOnSameDay(
        ShiftRule $rule,
        User $user,
        Carbon $date
    ): Collection {
        $violations = collect();

        // Get all shifts for this user on this date, ordered by start time
        $shifts = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
        ->whereDate('start_date', $date)
        ->orderBy('start')
        ->get();

        if ($shifts->count() < 2) {
            return $violations; // Need at least 2 shifts to check rest time between them
        }

        // Check rest time between consecutive shifts
        for ($i = 0; $i < $shifts->count() - 1; $i++) {
            $currentShift = $shifts[$i];
            $nextShift = $shifts[$i + 1];

            // Calculate end time of current shift (may go into next day)
            $currentShiftEnd = Carbon::parse($currentShift->end_date)->setTimeFromTimeString($currentShift->end);

            // Calculate start time of next shift
            $nextShiftStart = Carbon::parse($nextShift->start_date)->setTimeFromTimeString($nextShift->start);

            // Calculate rest hours between shifts
            $restHours = $this->calculateRestHours($currentShiftEnd, $nextShiftStart);

            if ($restHours < $rule->individual_number_value) {
                $violations->push($this->createViolation($rule, $nextShift, $user, $date, [
                    'rest_hours' => $restHours,
                    'min_required' => $rule->individual_number_value,
                    'previous_shift_end' => $currentShiftEnd->format('Y-m-d H:i:s'),
                    'current_shift_start' => $nextShiftStart->format('Y-m-d H:i:s')
                ]));
            }
        }

        return $violations;
    }

    protected function calculateRestHours(Carbon $endTime, Carbon $startTime): float
    {
        // Calculate the difference in hours - endTime is when last shift ended, startTime is when next shift starts
        // If start time is before end time, it means there's no gap (violation)
        if ($startTime <= $endTime) {
            return 0; // No rest time if shifts overlap or touch
        }

        return $endTime->diffInHours($startTime, false);
    }

    protected function isWorkday(Carbon $date): bool
    {
        return !$date->isSunday() && !$this->isHoliday($date);
    }

    protected function isHoliday(Carbon $date): bool
    {
        // Check if it's Sunday or check against holiday database
        if ($date->isSunday()) {
            return true;
        }

        $query = Holiday::query();

        // Check for holidays that match the exact date
        $exactDateMatch = $query->where('date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->exists();

        if ($exactDateMatch) {
            return true;
        }

        // Check for yearly recurring holidays
        $yearlyHolidays = Holiday::where('yearly', true)->get();

        foreach ($yearlyHolidays as $holiday) {
            if (!$holiday->date || !$holiday->end_date) {
                continue;
            }

            $holidayStart = Carbon::parse($holiday->date);
            $holidayEnd = Carbon::parse($holiday->end_date);

            // Create dates for this year with the same month/day as the holiday
            $thisYearStart = Carbon::create(
                $date->year,
                $holidayStart->month,
                $holidayStart->day
            );
            $thisYearEnd = Carbon::create(
                $date->year,
                $holidayEnd->month,
                $holidayEnd->day
            );

            // Handle end date in next year (e.g. Dec 31 - Jan 2)
            if ($thisYearEnd->lt($thisYearStart)) {
                $thisYearEnd->addYear();
            }

            if ($date->between($thisYearStart, $thisYearEnd)) {
                return true;
            }
        }

        return false;
    }

    protected function getEarliestShiftStartOfDay(User $user, Carbon $date): ?Carbon
    {
        $shift = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
        ->whereDate('start_date', $date)
        ->orderBy('start')
        ->first();

        return $shift ? Carbon::parse($shift->start_date)->setTimeFromTimeString($shift->start) : null;
    }

    protected function getLatestShiftEndOfDay(User $user, Carbon $date): ?Carbon
    {
        // Find shifts that end on the given date OR start on the given date and go past midnight
        $shift = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->where(function ($query) use ($date): void {
                $query->whereDate('end_date', $date)
                    ->orWhere(function ($q) use ($date): void {
                        $q->whereDate('start_date', $date)
                            ->whereRaw('end_date > start_date'); // Shift goes past midnight
                    });
            })
            ->orderByDesc('end_date')
            ->orderByDesc('end')
            ->first();

        if (!$shift) {
            return null;
        }

        // If shift ends on the same date as start, use that date
        if ($shift->start_date === $shift->end_date) {
            return Carbon::parse($date->format('Y-m-d'))->setTimeFromTimeString($shift->end);
        }

        // If shift goes past midnight, use the actual end date
        return Carbon::parse($shift->end_date)->setTimeFromTimeString($shift->end);
    }
}
