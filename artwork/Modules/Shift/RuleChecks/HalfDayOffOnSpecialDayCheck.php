<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Support\ShiftRuleConfig;
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
 *
 * Zusatzparameter (ShiftRuleConfig):
 *  - include_sundays: die Regel gilt auch an gewöhnlichen Sonntagen.
 *  - include_planned: neben gewährten halben Ersatzfreitagen zählen auch geplante halbe freie Tage
 *    aus dem Tagesstatus "Frei" (Vormittag/Nachmittag).
 *  - morning_only: nur ein halber freier Tag am Vormittag ist ein Verstoß (NV Bühne: kein halber
 *    freier Tag am Vormittag eines Sonntags oder Wochenfeiertags).
 */
class HalfDayOffOnSpecialDayCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        // Preload all granted halves for the whole range once and group by date. Only days that actually
        // have a granted half can violate this rule, so the (more expensive) special-day lookup runs only
        // for those few days instead of for every day in the range.
        $halvesByDate = $this->getHalfDaysOffByDate(
            $user,
            $startDate,
            $endDate,
            (bool) ShiftRuleConfig::get($rule, 'include_planned')
        );
        $includeSundays = (bool) ShiftRuleConfig::get($rule, 'include_sundays');
        $morningOnly = (bool) ShiftRuleConfig::get($rule, 'morning_only');

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $halves = $halvesByDate->get($date->format('Y-m-d')) ?? collect();
            if ($halves->isEmpty()) {
                continue;
            }

            if ($morningOnly) {
                $halves = $halves->filter(fn ($half): bool => $half->half_day_period === 'morning')->values();
                if ($halves->isEmpty()) {
                    continue;
                }
            }

            $isSpecialDay = $this->isSpecialDay($date);
            if (!$isSpecialDay && !($includeSundays && $date->isSunday())) {
                continue;
            }

            $violations->push($this->createViolationWithoutShift($rule, $user, $date, [
                'reason' => 'half_day_off_on_special_day',
                'half_day_period' => $halves->first()->half_day_period,
                'value' => (float) $halves->sum('value'),
                'on_sunday' => !$isSpecialDay,
            ]));
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'halfDayOffOnSpecialDay';
    }
}
