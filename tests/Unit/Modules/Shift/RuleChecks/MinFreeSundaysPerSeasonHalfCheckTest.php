<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\MinFreeSundaysPerSeasonHalfCheck;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Mindestens X freie Sonntage (mit Sa/Mo) je Spielzeithälfte: Verstoß ohne Schicht auf den letzten
 * Sonntag der Hälfte, sobald ist + noch möglich < X.
 */
final class MinFreeSundaysPerSeasonHalfCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private MinFreeSundaysPerSeasonHalfCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new MinFreeSundaysPerSeasonHalfCheck();
    }

    protected function tearDown(): void
    {
        $this->configureSeason(null, null);
        parent::tearDown();
    }

    private function rule(int $target = 3): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'minFreeSundaysPerSeasonHalf',
            'individual_number_value' => $target,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function violation_when_the_target_is_no_longer_reachable_in_the_running_half(): void
    {
        [$user] = $this->userWithContract();
        $today = Carbon::today();
        // Spielzeit: vor 2 Wochen bis in 4 Wochen -> Mitte in 1 Woche, 1. Hälfte endet in 6 Tagen
        $seasonStart = $today->copy()->subWeeks(2);
        $seasonEnd = $today->copy()->addWeeks(4);
        $this->configureSeason($seasonStart, $seasonEnd);

        $violations = $this->check->check($this->rule(3), $user, $today->copy(), $today->copy()->addDays(14));

        // 1. Hälfte: ist 0 (keine freien Sonntage mit Sa/Mo in der Vergangenheit), höchstens 1 Sonntag
        // noch möglich -> 3 nicht erreichbar. 2. Hälfte hat noch nicht begonnen -> kein Verstoß.
        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertNull($violation->shift_id);
        $this->assertSame(1, $violation->violation_data['half']);
        $this->assertSame(3, $violation->violation_data['target']);
        $this->assertSame(0, $violation->violation_data['have']);
        $this->assertLessThanOrEqual(1, $violation->violation_data['possible']);
        $this->assertSame(Carbon::SUNDAY, $violation->violation_date->dayOfWeek);
        $this->assertLessThanOrEqual(
            Carbon::parse($violation->violation_data['half_end']),
            $violation->violation_date
        );
    }

    #[Test]
    public function no_violation_while_the_target_is_still_reachable(): void
    {
        [$user] = $this->userWithContract();
        $today = Carbon::today();
        // Spielzeit: vor 1 Woche bis in 1 Jahr -> heute in der 1. Hälfte, noch ~25 Sonntage möglich
        $this->configureSeason($today->copy()->subWeek(), $today->copy()->addYear());

        $violations = $this->check->check($this->rule(3), $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function sundays_with_shifts_do_not_count_as_possible(): void
    {
        [$user] = $this->userWithContract();
        $today = Carbon::today();
        $seasonStart = $today->copy()->subWeeks(2);
        $seasonEnd = $today->copy()->addWeeks(6);
        $this->configureSeason($seasonStart, $seasonEnd);

        // Jeden kommenden Sonntag der Spielzeit mit einer Schicht belegen -> 0 möglich
        $cursor = $today->copy();
        while ($cursor->lte($seasonEnd)) {
            if ($cursor->isSunday()) {
                $this->shiftFor($user, $cursor->copy());
            }
            $cursor->addDay();
        }

        $violations = $this->check->check($this->rule(1), $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertGreaterThanOrEqual(1, $violations->count());
        $this->assertSame(0, $violations->first()->violation_data['possible']);
    }

    #[Test]
    public function without_configured_season_nothing_happens(): void
    {
        [$user] = $this->userWithContract();
        $this->configureSeason(null, null);

        $violations = $this->check->check($this->rule(3), $user, Carbon::today(), Carbon::today()->addDays(14));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function without_rule_value_the_contract_target_is_used(): void
    {
        [$user] = $this->userWithContract(
            ['free_sundays_sat_mon_per_half' => 2, 'free_sundays_sat_mon_per_half_active' => true],
            ['free_sundays_sat_mon_per_half' => 2, 'free_sundays_sat_mon_per_half_active' => true]
        );
        $today = Carbon::today();
        $this->configureSeason($today->copy()->subWeeks(2), $today->copy()->addWeeks(4));

        $violations = $this->check->check($this->rule(0), $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertCount(1, $violations);
        $this->assertSame(2, $violations->first()->violation_data['target']);
    }
}
