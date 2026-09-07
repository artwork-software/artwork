<?php

namespace Artwork\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\ContractSettingsResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Mindestens X ganze freie Tage pro Woche (NV Bühne).
 *
 * X = $rule->individual_number_value; leer/0 = Vertragswert free_full_days_per_week (Zuweisung vor
 * Vorlage, ContractSettingsResolver). Ist auch dieser 0/leer, greift die Regel nicht.
 *
 * Freier Tag = Kalendertag ohne Schicht (effektive Pivot-Zeiten, Über-Mitternacht-Schichten belegen
 * beide Tage) UND ohne individuelle Zeit. Verstoß je Kalenderwoche (Mo–So) OHNE Schichtbezug auf den
 * Sonntag der Woche, wenn die Woche weniger als X freie Tage hat (Warnung; abgeschlossene Woche: Fehler).
 */
class MinFreeDaysPerWeekCheck extends AbstractRuleCheck
{
    public function check(ShiftRule $rule, User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        $target = $this->targetFor($rule, $user);
        if ($target <= 0) {
            return $violations;
        }

        $firstMonday = $startDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $lastSunday = $endDate->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $occupied = $this->getOccupiedDayKeys($user, $firstMonday, $lastSunday);
        $today = now()->startOfDay();

        for ($monday = $firstMonday->copy(); $monday->lte($lastSunday); $monday->addWeek()) {
            $sunday = $monday->copy()->addDays(6);

            $freeDays = 0;
            for ($day = $monday->copy(); $day->lte($sunday); $day->addDay()) {
                if (!isset($occupied[$day->toDateString()])) {
                    $freeDays++;
                }
            }

            if ($freeDays >= $target) {
                continue;
            }

            $completed = $sunday->lt($today);

            $violations->push($this->createViolationWithoutShift($rule, $user, $sunday, [
                'type' => 'min_free_days_per_week',
                'free_days' => $freeDays,
                'target' => $target,
                'week' => $monday->isoWeek(),
                'week_start' => $monday->toDateString(),
                'week_end' => $sunday->toDateString(),
                'completed' => $completed,
            ], $completed ? 'error' : 'warning'));
        }

        return $violations;
    }

    /**
     * Die Verstöße liegen auf dem Sonntag jeder berührten Kalenderwoche.
     */
    public function getCoveredRange(ShiftRule $rule, Carbon $startDate, Carbon $endDate): ?array
    {
        return [
            $startDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
            $endDate->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
        ];
    }

    private function targetFor(ShiftRule $rule, User $user): int
    {
        $ruleValue = (int) round((float) $rule->individual_number_value);
        if ($ruleValue > 0) {
            return $ruleValue;
        }

        return app(ContractSettingsResolver::class)->int($user, 'free_full_days_per_week', 0);
    }

    public function getTriggerType(): string
    {
        return 'minFreeDaysPerWeek';
    }
}
