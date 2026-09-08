<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\RestTimeBeforeWorkdayCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

final class RestTimeBeforeWorkdayCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private RestTimeBeforeWorkdayCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new RestTimeBeforeWorkdayCheck();
    }

    private function rule(float $hours = 11.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'restTimeBeforeWorkday',
            'individual_number_value' => $hours,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function violation_when_rest_before_a_workday_is_too_short(): void
    {
        $user = User::factory()->create();
        $tuesday = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $tuesday->copy()->subDay(), '14:00:00', '23:00:00'); // Mo bis 23:00
        $this->shiftFor($user, $tuesday, '06:00:00', '14:00:00'); // Di ab 06:00 -> 7 h Ruhe

        $violations = $this->check->check($this->rule(11.0), $user, $tuesday->copy(), $tuesday->copy());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($tuesday->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(7.0, $violation->violation_data['rest_hours'], 0.01);
        $this->assertEqualsWithDelta(11.0, $violation->violation_data['min_required'], 0.01);
    }

    #[Test]
    public function no_violation_when_rest_is_sufficient(): void
    {
        $user = User::factory()->create();
        $tuesday = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $tuesday->copy()->subDay(), '08:00:00', '16:00:00');
        $this->shiftFor($user, $tuesday, '08:00:00', '16:00:00'); // 16 h Ruhe

        $violations = $this->check->check($this->rule(11.0), $user, $tuesday->copy(), $tuesday->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function sunday_is_not_a_workday_for_this_rule(): void
    {
        $user = User::factory()->create();
        $sunday = $this->futureWeekday(Carbon::SUNDAY);
        $this->shiftFor($user, $sunday->copy()->subDay(), '14:00:00', '23:00:00');
        $this->shiftFor($user, $sunday, '06:00:00', '14:00:00');

        $violations = $this->check->check($this->rule(11.0), $user, $sunday->copy(), $sunday->copy());

        // Sonntag ist Sache von restTimeBeforeHoliday
        $this->assertCount(0, $violations);
    }

    #[Test]
    public function pivot_times_of_the_person_override_the_shift_times(): void
    {
        $user = User::factory()->create();
        $tuesday = $this->futureWeekday(Carbon::TUESDAY);
        // Schicht Mo 14:00–20:00, die Person selbst aber bis 23:30 (Pivot) -> Di 06:00 = 6,5 h Ruhe
        $this->shiftWithPivotTimesFor($user, $tuesday->copy()->subDay(), '14:00:00', '20:00:00', '14:00:00', '23:30:00');
        $this->shiftFor($user, $tuesday, '06:00:00', '14:00:00');

        $violations = $this->check->check($this->rule(11.0), $user, $tuesday->copy(), $tuesday->copy());

        $this->assertCount(1, $violations);
        $this->assertEqualsWithDelta(6.5, $violations->first()->violation_data['rest_hours'], 0.01);
    }

    #[Test]
    public function pivot_times_can_also_resolve_a_violation_of_the_shift_times(): void
    {
        $user = User::factory()->create();
        $tuesday = $this->futureWeekday(Carbon::TUESDAY);
        // Schicht Mo bis 23:00, die Person geht laut Pivot schon 17:00 -> Di 06:00 = 13 h Ruhe
        $this->shiftWithPivotTimesFor($user, $tuesday->copy()->subDay(), '14:00:00', '23:00:00', '14:00:00', '17:00:00');
        $this->shiftFor($user, $tuesday, '06:00:00', '14:00:00');

        $violations = $this->check->check($this->rule(11.0), $user, $tuesday->copy(), $tuesday->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function individual_times_still_count_in_addition_to_shifts(): void
    {
        $user = User::factory()->create();
        $tuesday = $this->futureWeekday(Carbon::TUESDAY);
        $this->individualTimeFor($user, $tuesday->copy()->subDay(), '18:00', '23:00');
        $this->shiftFor($user, $tuesday, '06:00:00', '14:00:00'); // 7 h Ruhe nach individueller Zeit

        $violations = $this->check->check($this->rule(11.0), $user, $tuesday->copy(), $tuesday->copy());

        $this->assertCount(1, $violations);
        $this->assertEqualsWithDelta(7.0, $violations->first()->violation_data['rest_hours'], 0.01);
    }
}
