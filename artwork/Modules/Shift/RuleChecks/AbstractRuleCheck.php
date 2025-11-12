<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
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
        // Sum minutes from shifts overlapping this date
        $shifts = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $totalMinutes = 0;
        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->start_date)->setTimeFromTimeString($shift->start);
            $end = Carbon::parse($shift->end_date)->setTimeFromTimeString($shift->end);
            $breakMinutes = $shift->break_minutes ?? 0;

            // Clip to the day window
            $segStart = $start->greaterThan($dayStart) ? $start : $dayStart;
            $segEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;

            $minutes = max(0, $segStart->diffInMinutes($segEnd) - $breakMinutes);
            $totalMinutes += $minutes;
        }

        // Add minutes from IndividualTimes overlapping this date
        $individualTimes = $user->individualTimes()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        foreach ($individualTimes as $it) {
            $itStart = Carbon::parse($it->start_date);
            $itEnd = Carbon::parse($it->end_date);

            if (!empty($it->start_time)) {
                $itStart->setTimeFromTimeString($it->start_time);
            } else {
                $itStart->startOfDay();
            }
            if (!empty($it->end_time)) {
                $itEnd->setTimeFromTimeString($it->end_time);
            } else {
                $itEnd->endOfDay();
            }

            $segStart = $itStart->greaterThan($dayStart) ? $itStart : $dayStart;
            $segEnd = $itEnd->lessThan($dayEnd) ? $itEnd : $dayEnd;

            $minutes = max(0, $segStart->diffInMinutes($segEnd));
            $totalMinutes += $minutes;
        }

        return $totalMinutes / 60.0;
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

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        // Build a list of work segments (shifts + individual times) on this date
        $segments = [];

        // Shifts
        $shifts = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->whereDate('start_date', $date)
            ->orderBy('start')
            ->get();
        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->start_date)->setTimeFromTimeString($shift->start);
            $end = Carbon::parse($shift->end_date)->setTimeFromTimeString($shift->end);
            // clip to day
            $segStart = $start->greaterThan($dayStart) ? $start : $dayStart;
            $segEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;
            if ($segStart < $segEnd) {
                $segments[] = ['start' => $segStart, 'end' => $segEnd, 'type' => 'shift', 'shift' => $shift];
            }
        }

        // Individual times
        $individualTimes = $user->individualTimes()
            ->whereDate('start_date', $date)
            ->orderByRaw('COALESCE(start_time, "00:00:00")')
            ->get();
        foreach ($individualTimes as $it) {
            $itStart = Carbon::parse($it->start_date);
            $itEnd = Carbon::parse($it->end_date);
            $itStart->setTimeFromTimeString($it->start_time ?: '00:00:00');
            $itEnd->setTimeFromTimeString($it->end_time ?: '23:59:59');
            $segStart = $itStart->greaterThan($dayStart) ? $itStart : $dayStart;
            $segEnd = $itEnd->lessThan($dayEnd) ? $itEnd : $dayEnd;
            if ($segStart < $segEnd) {
                $segments[] = ['start' => $segStart, 'end' => $segEnd, 'type' => 'it', 'shift' => null];
            }
        }

        if (count($segments) < 2) {
            return $violations;
        }

        // Sort segments by start time
        usort($segments, function ($a, $b) {
            if ($a['start']->eq($b['start'])) {
                return 0;
            }
            return $a['start']->lt($b['start']) ? -1 : 1;
        });

        // Check rest time between consecutive segments; create violation when the NEXT segment is a shift
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $current = $segments[$i];
            $next = $segments[$i + 1];
            $restHours = $this->calculateRestHours($current['end'], $next['start']);
            if ($restHours < $rule->individual_number_value && $next['type'] === 'shift' && $next['shift']) {
                $violations->push($this->createViolation($rule, $next['shift'], $user, $date, [
                    'rest_hours' => $restHours,
                    'min_required' => $rule->individual_number_value,
                    'previous_segment_end' => $current['end']->format('Y-m-d H:i:s'),
                    'current_segment_start' => $next['start']->format('Y-m-d H:i:s'),
                    'next_segment_type' => 'shift',
                ]));
            }
        }

        return $violations;
    }

    protected function calculateRestHours(Carbon $endTime, Carbon $startTime): float
    {
        // Calculate the difference in hours - endTime is when last shift ended, startTime is when next shift starts
        // If start time is before or equal to end time, it means there's no gap (or overlap)
        if ($startTime <= $endTime) {
            return 0.0; // No rest time if shifts overlap or touch
        }

        // Always return a positive fractional hour difference for precise comparisons
        return $endTime->diffInMinutes($startTime) / 60.0;
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
        // Earliest shift start on this date
        $shift = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->whereDate('start_date', $date)
            ->orderBy('start')
            ->first();
        $earliest = $shift ? Carbon::parse($shift->start_date)->setTimeFromTimeString($shift->start) : null;

        // Earliest individual time start on this date
        $it = $user->individualTimes()
            ->whereDate('start_date', $date)
            ->orderByRaw('COALESCE(start_time, "00:00:00")')
            ->first();
        if ($it) {
            $itStart = Carbon::parse($it->start_date);
            $itStart->setTimeFromTimeString($it->start_time ?: '00:00:00');
            $earliest = $earliest ? $earliest->min($itStart) : $itStart;
        }

        return $earliest;
    }

    protected function getLatestShiftEndOfDay(User $user, Carbon $date): ?Carbon
    {
        // Find latest shift end relevant for this date
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

        $latest = null;
        if ($shift) {
            if ($shift->start_date === $shift->end_date) {
                $latest = Carbon::parse($date->format('Y-m-d'))->setTimeFromTimeString($shift->end);
            } else {
                $latest = Carbon::parse($shift->end_date)->setTimeFromTimeString($shift->end);
            }
        }

        // Consider IndividualTimes ending on this date or starting on this date and spanning into next day
        $it = $user->individualTimes()
            ->where(function ($q) use ($date): void {
                $q->whereDate('end_date', $date)
                    ->orWhere(function ($qq) use ($date): void {
                        $qq->whereDate('start_date', $date)
                            ->whereRaw('end_date > start_date');
                    });
            })
            ->orderByDesc('end_date')
            ->orderByRaw('COALESCE(end_time, "23:59:59") DESC')
            ->first();

        if ($it) {
            $itEndDate = Carbon::parse($it->end_date);
            $itEndDate->setTimeFromTimeString($it->end_time ?: '23:59:59');
            $latest = $latest ? $latest->max($itEndDate) : $itEndDate;
        }

        return $latest;
    }
}
