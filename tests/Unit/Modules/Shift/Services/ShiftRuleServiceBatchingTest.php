<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Batching (Datenkontext je Lauf): Die Zahl der Abfragen je Person und Lauf ist unabhängig von der
 * Anzahl der geprüften Tage. Absolute Obergrenze bewusst mit Luft — nach Messung ggf. nachziehen.
 */
final class ShiftRuleServiceBatchingTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    /** Obergrenze je Person und Lauf (14 Tage, 9 Regeln) — nach Messung anpassen. */
    private const MAX_QUERIES_PER_USER = 15;

    private const RULE_TYPES = [
        'maxWorkingHoursOnDay' => 10.0,
        'maxConsecWorkingDays' => 6.0,
        'weeklyMaxHours' => 48.0,
        'restTimeBeforeWorkday' => 11.0,
        'restTimeBeforeHoliday' => 11.0,
        'restTimeBetweenShiftGroups' => 11.0,
        'halfDayOffConflict' => 14.0,
        'halfDayOffOnSpecialDay' => 0.0,
        'workOnSunday' => 0.0,
    ];

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function prepareUser(): User
    {
        Bus::fake(); // Regel-/Vertragsänderungen sollen hier keine Hintergrundläufe auslösen
        [$user, $contract] = $this->userWithContract();
        foreach (self::RULE_TYPES as $type => $value) {
            $this->ruleForContract($contract, $type, $value);
        }
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Drei unauffällige Schichten (8 h) + eine individuelle Zeit
        $this->shiftFor($user, $monday);
        $this->shiftFor($user, $monday->copy()->addDays(2));
        $this->shiftFor($user, $monday->copy()->addDays(9));
        $this->individualTimeFor($user, $monday->copy()->addDays(4), '09:00', '12:00');

        return $user;
    }

    #[Test]
    public function query_count_per_user_does_not_grow_with_the_number_of_days(): void
    {
        $user = $this->prepareUser();
        $service = app(ShiftRuleService::class);
        $start = $this->futureWeekday(Carbon::MONDAY);

        $queriesForThreeDays = $this->countQueries(
            fn () => $service->validateRulesForUser($user, $start->copy(), $start->copy()->addDays(2))
        );
        $queriesForFourteenDays = $this->countQueries(
            fn () => $service->validateRulesForUser($user, $start->copy(), $start->copy()->addDays(13))
        );

        $this->assertLessThanOrEqual(
            $queriesForThreeDays + 2,
            $queriesForFourteenDays,
            "Queries wachsen mit der Tagesanzahl: 3 Tage = {$queriesForThreeDays}, 14 Tage = {$queriesForFourteenDays}"
        );
        $this->assertLessThanOrEqual(
            self::MAX_QUERIES_PER_USER,
            $queriesForFourteenDays,
            "Zu viele Queries je Person und Lauf: {$queriesForFourteenDays}"
        );
    }

    #[Test]
    public function context_and_direct_queries_produce_the_same_violations(): void
    {
        Bus::fake();
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'restTimeBeforeWorkday', 11.0);
        $tuesday = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftWithPivotTimesFor($user, $tuesday->copy()->subDay(), '14:00:00', '20:00:00', '14:00:00', '23:30:00');
        $this->shiftFor($user, $tuesday, '06:00:00', '14:00:00');

        // Mit Kontext (Service) …
        $withContext = app(ShiftRuleService::class)
            ->validateRulesForUser($user, $tuesday->copy(), $tuesday->copy());
        // … und ohne Kontext (Check direkt) liefern dasselbe Ergebnis
        $direct = (new \Artwork\Modules\Shift\RuleChecks\RestTimeBeforeWorkdayCheck())
            ->check($rule, $user, $tuesday->copy(), $tuesday->copy());

        $this->assertCount(1, $withContext);
        $this->assertCount(1, $direct);
        $this->assertSame($withContext->first()->id, $direct->first()->id);
        $this->assertEqualsWithDelta(6.5, $direct->first()->violation_data['rest_hours'], 0.01);
    }
}
