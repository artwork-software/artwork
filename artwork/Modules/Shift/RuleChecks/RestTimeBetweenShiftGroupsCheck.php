<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Checks the rest time between two shifts of DIFFERENT shift groups on the same day.
 *
 * Only shifts that have a shift group are considered ("no shift group" does not count as a
 * different group). When a user works two shifts on the same day whose shift groups differ,
 * the rest time (end of the earlier shift -> start of the later shift) must be at least
 * $rule->individual_number_value hours. Overlapping or directly adjacent shifts (0 hours rest)
 * therefore always produce a violation.
 */
class RestTimeBetweenShiftGroupsCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $dateRange = CarbonPeriod::create($startDate, $endDate);

        foreach ($dateRange as $date) {
            $violations = $violations->concat($this->checkShiftGroupRestForDay($rule, $user, $date));
        }

        return $violations;
    }

    private function checkShiftGroupRestForDay(ShiftRule $rule, User $user, Carbon $date): Collection
    {
        $violations = collect();

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        // Only shifts that actually belong to a shift group are relevant for this rule.
        $shifts = Shift::whereHas('users', function ($query) use ($user): void {
            $query->where('users.id', $user->id);
        })
            ->whereNotNull('shift_group_id')
            ->whereDate('start_date', $date)
            ->with('shiftGroup')
            ->orderBy('start')
            ->get();

        $segments = [];
        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->start_date)->setTimeFromTimeString($shift->start);
            $end = Carbon::parse($shift->end_date)->setTimeFromTimeString($shift->end);
            // clip to day
            $segStart = $start->greaterThan($dayStart) ? $start : $dayStart;
            $segEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;
            if ($segStart < $segEnd) {
                $segments[] = [
                    'start' => $segStart,
                    'end' => $segEnd,
                    'shift' => $shift,
                    'group_id' => $shift->shift_group_id,
                ];
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

        // Check rest time between consecutive shifts whose shift groups differ.
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $current = $segments[$i];
            $next = $segments[$i + 1];

            // Same shift group -> no rest-time requirement between them for this rule.
            if ($current['group_id'] === $next['group_id']) {
                continue;
            }

            // Overlapping/adjacent shifts return 0.0 here -> counts as a violation (0 < X).
            $restHours = $this->calculateRestHours($current['end'], $next['start']);
            if ($restHours < $rule->individual_number_value) {
                $violations->push($this->createViolation($rule, $next['shift'], $user, $date, [
                    'rest_hours' => $restHours,
                    'min_required' => $rule->individual_number_value,
                    'previous_segment_end' => $current['end']->format('Y-m-d H:i:s'),
                    'current_segment_start' => $next['start']->format('Y-m-d H:i:s'),
                    'previous_shift_group_id' => $current['group_id'],
                    'current_shift_group_id' => $next['group_id'],
                    'previous_shift_group_name' => $current['shift']->shiftGroup?->name,
                    'current_shift_group_name' => $next['shift']->shiftGroup?->name,
                ]));
            }
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'restTimeBetweenShiftGroups';
    }
}
