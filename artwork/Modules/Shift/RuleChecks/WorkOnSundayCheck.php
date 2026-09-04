<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Sonntagsarbeit: ein Verstoß (severity warning) für jeden Sonntag im Zeitraum, an dem die
 * Person eine Schicht hat. Schichttag = effektiver Starttag der Person (Pivot-Datum, sonst
 * start_date), bei Schichten über Mitternacht zählt also der Starttag. $rule->individual_number_value wird nicht verwendet.
 */
class WorkOnSundayCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            if (!$date->isSunday()) {
                continue;
            }

            $shift = $this->getShiftStartingOnDate($user, $date);
            if (!$shift) {
                continue;
            }

            $violations->push($this->createViolation($rule, $shift, $user, $date, [
                'day' => $date->toDateString(),
                'weekday' => 'sunday',
                // effektiver Beginn der Person (Pivot-Zeit vor Schichtzeit)
                'shift_start' => $this->shiftInterval($shift)['start']->format('Y-m-d H:i:s'),
            ]));
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'workOnSunday';
    }
}
