<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Checks conflicts between granted half compensation days off (HFT) and shifts on the same day.
 *
 * The threshold time T is encoded in $rule->individual_number_value as a decimal hour
 * (14:00 -> 14.0, 14:30 -> 14.5). Per granted half day on a date:
 *   - only morning HFT + shift  -> violation when the shift starts before T (must start >= T)
 *   - only afternoon HFT + shift -> violation when the shift ends after T (must end <= T)
 *   - morning AND afternoon HFT + any shift -> always a violation (whole day off)
 */
class HalfDayOffConflictCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $repository = app(CompensationDayOffRepository::class);

        // Decode the decimal-hour threshold (e.g. 14.5 -> 14:30).
        $thresholdHour = (int) floor($rule->individual_number_value);
        $thresholdMinute = (int) round(($rule->individual_number_value - $thresholdHour) * 60);

        // Preload all granted halves for the whole range once and group by date (avoids a per-day query).
        $halvesByDate = $repository
            ->getGrantedHalvesForUserInRange($user->id, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->groupBy(fn ($half): string => Carbon::parse($half->granted_date)->format('Y-m-d'));

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $halves = $halvesByDate->get($date->format('Y-m-d')) ?? collect();
            if ($halves->isEmpty()) {
                continue;
            }

            $hasMorning = $halves->contains(fn ($half): bool => $half->half_day_period === 'morning');
            $hasAfternoon = $halves->contains(fn ($half): bool => $half->half_day_period === 'afternoon');

            if (!$hasMorning && !$hasAfternoon) {
                continue;
            }

            $shift = $this->getShiftForUserOnDate($user, $date);
            if (!$shift) {
                continue;
            }

            $threshold = $date->copy()->setTime($thresholdHour, $thresholdMinute);
            $shiftStart = $this->getEarliestShiftStartOfDay($user, $date);
            $shiftEnd = $this->getLatestShiftEndOfDay($user, $date);

            $isViolation = false;
            $reasonCase = null;
            $period = null;

            if ($hasMorning && $hasAfternoon) {
                // Whole day off -> any shift conflicts.
                $isViolation = true;
                $reasonCase = 'c';
                $period = 'both';
            } elseif ($hasMorning) {
                // Morning off -> shift must start at or after the threshold.
                $period = 'morning';
                $reasonCase = 'a';
                $isViolation = $shiftStart && $shiftStart->lt($threshold);
            } elseif ($hasAfternoon) {
                // Afternoon off -> shift must end at or before the threshold.
                $period = 'afternoon';
                $reasonCase = 'b';
                $isViolation = $shiftEnd && $shiftEnd->gt($threshold);
            }

            if ($isViolation) {
                $violations->push($this->createViolation($rule, $shift, $user, $date, [
                    'half_day_period' => $period,
                    'shift_start' => $shiftStart?->format('Y-m-d H:i:s'),
                    'shift_end' => $shiftEnd?->format('Y-m-d H:i:s'),
                    'threshold_hour' => $rule->individual_number_value,
                    'reason_case' => $reasonCase,
                ]));
            }
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'halfDayOffConflict';
    }
}
