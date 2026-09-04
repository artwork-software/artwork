<?php

namespace Artwork\Modules\Shift\RuleChecks;

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
 * therefore always produce a violation. Gerechnet wird mit den effektiven Zeiten der Person
 * (Pivot-Zeit aus shift_workers, sonst Schichtzeit).
 */
class RestTimeBetweenShiftGroupsCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        // Effektive Arbeitsintervalle der Person (Pivot-Zeit vor Schichtzeit), nur Schichten mit
        // Schichtgruppe; einmal für den ganzen Zeitraum geladen und nach effektivem Starttag gruppiert.
        $intervalsByDate = [];
        foreach ($this->getWorkIntervals($user, $startDate, $endDate, false) as $interval) {
            if ($interval['group_id'] === null) {
                continue;
            }
            $intervalsByDate[$interval['start_key']][] = $interval;
        }

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $intervals = $intervalsByDate[$date->toDateString()] ?? [];
            $violations = $violations->concat($this->checkShiftGroupRestForDay($rule, $user, $date, $intervals));
        }

        return $violations;
    }

    /**
     * @param list<array<string, mixed>> $intervals effektive Schichtintervalle, die an diesem Tag beginnen
     */
    private function checkShiftGroupRestForDay(ShiftRule $rule, User $user, Carbon $date, array $intervals): Collection
    {
        $violations = collect();

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $segments = [];
        foreach ($intervals as $interval) {
            // clip to day
            $segStart = $interval['start']->greaterThan($dayStart) ? $interval['start']->copy() : $dayStart->copy();
            $segEnd = $interval['end']->lessThan($dayEnd) ? $interval['end']->copy() : $dayEnd->copy();
            if ($segStart < $segEnd) {
                $segments[] = [
                    'start' => $segStart,
                    'end' => $segEnd,
                    'shift' => $interval['shift'],
                    'group_id' => $interval['group_id'],
                ];
            }
        }

        if (count($segments) < 2) {
            return $violations;
        }

        // Sort segments by start time
        usort($segments, static function (array $a, array $b): int {
            return $a['start']->getTimestamp() <=> $b['start']->getTimestamp();
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
