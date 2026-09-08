<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Arbeit an Sondertagen: ein Verstoß (severity warning) für jeden Tag im Zeitraum, an dem die Person
 * eine Schicht hat (Schichttag = start_date) UND der laut SpecialDayService::countsAsSpecialDayForUser
 * ein Sondertag ist — Feiertag mit Flag "als Sondertag behandeln" und aktivem Vertragsschalter
 * special_day_rule_active. Schulferien ohne Flag lösen nicht aus, Sonntage sind Sache von workOnSunday.
 *
 * violation_data dokumentiert den Anspruch auf einen Ersatzruhetag (entitlement =>
 * replacement_rest_day); for_holiday => true belegt im Bearbeiten-Modal das Häkchen
 * "Ersatzfrei für Feiertag" vor. $rule->individual_number_value wird nicht verwendet.
 */
class WorkOnHolidayCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $specialDayService = $this->specialDayService();

        // Erst die (billigen) Sondertage des Zeitraums holen, dann nur für diese Tage Schichten laden.
        $specialDays = $this->getSpecialDaysBetween($startDate, $endDate);
        if ($specialDays === []) {
            return $violations;
        }

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $holidayName = $specialDays[$date->toDateString()] ?? null;
            if ($holidayName === null) {
                continue;
            }

            if (!$specialDayService->countsAsSpecialDayForUser($user, $date)) {
                // Vertragsschalter aus: Sondertage zählen für diese Person nicht.
                return $violations;
            }

            $shift = $this->getShiftStartingOnDate($user, $date);
            if (!$shift) {
                continue;
            }

            $violations->push($this->createViolation($rule, $shift, $user, $date, [
                'day' => $date->toDateString(),
                'holiday_name' => $holidayName,
                'for_holiday' => true,
                'entitlement' => 'replacement_rest_day',
                // effektiver Beginn der Person (Pivot-Zeit vor Schichtzeit)
                'shift_start' => $this->shiftInterval($shift)['start']->format('Y-m-d H:i:s'),
            ]));
        }

        return $violations;
    }

    public function getTriggerType(): string
    {
        return 'workOnHoliday';
    }
}
