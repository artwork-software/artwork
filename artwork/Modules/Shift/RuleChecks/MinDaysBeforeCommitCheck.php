<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MinDaysBeforeCommitCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
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

    public function getTriggerType(): string
    {
        return 'minDaysBeforeCommit';
    }

}