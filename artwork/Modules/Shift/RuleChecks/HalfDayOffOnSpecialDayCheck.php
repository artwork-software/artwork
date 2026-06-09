<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Disallows half compensation days off (HFT) on "Sondertage" (holidays with treatAsSpecialDay = true).
 *
 * For each day in the range: if the day is a special day and the user has a granted half compensation
 * day (value < 1.0) on it, a violation is created. The violation is not tied to a shift.
 * $rule->individual_number_value is not used by this rule.
 */
class HalfDayOffOnSpecialDayCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $repository = app(CompensationDayOffRepository::class);

        // Preload all granted halves for the whole range once and group by date. Only days that actually
        // have a granted half can violate this rule, so the (more expensive) special-day lookup runs only
        // for those few days instead of for every day in the range.
        $halvesByDate = $repository
            ->getGrantedHalvesForUserInRange($user->id, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->groupBy(fn ($half): string => Carbon::parse($half->granted_date)->format('Y-m-d'));

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $halves = $halvesByDate->get($date->format('Y-m-d')) ?? collect();
            if ($halves->isEmpty()) {
                continue;
            }

            if (!$this->isSpecialDay($date)) {
                continue;
            }

            $violations->push($this->createViolationWithoutShift($rule, $user, $date, [
                'reason' => 'half_day_off_on_special_day',
                'half_day_period' => $halves->first()->half_day_period,
                'value' => (float) $halves->sum('value'),
            ]));
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'halfDayOffOnSpecialDay';
    }
}
