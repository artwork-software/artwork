<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\RestTimeBeforeHolidayCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

final class RestTimeBeforeHolidayCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private RestTimeBeforeHolidayCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new RestTimeBeforeHolidayCheck();
    }

    private function rule(float $hours = 11.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'restTimeBeforeHoliday',
            'individual_number_value' => $hours,
            'is_active' => true,
        ]);
    }

    private function lateShiftThenEarlyShift(User $user, Carbon $day): void
    {
        $this->shiftFor($user, $day->copy()->subDay(), '14:00:00', '23:00:00');
        $this->shiftFor($user, $day, '06:00:00', '14:00:00'); // 7 h Ruhe
    }

    #[Test]
    public function sunday_triggers_the_rule(): void
    {
        $user = User::factory()->create();
        $sunday = $this->futureWeekday(Carbon::SUNDAY);
        $this->lateShiftThenEarlyShift($user, $sunday);

        $violations = $this->check->check($this->rule(11.0), $user, $sunday->copy(), $sunday->copy());

        $this->assertCount(1, $violations);
        $this->assertSame($sunday->toDateString(), $violations->first()->violation_date->toDateString());
    }

    #[Test]
    public function flagged_public_holiday_on_a_weekday_triggers_the_rule(): void
    {
        $user = User::factory()->create();
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);
        $this->holiday($wednesday, 'Feiertag', true);
        $this->lateShiftThenEarlyShift($user, $wednesday);

        $violations = $this->check->check($this->rule(11.0), $user, $wednesday->copy(), $wednesday->copy());

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function school_holidays_without_special_day_flag_do_not_trigger(): void
    {
        $user = User::factory()->create();
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);
        $this->holiday($wednesday, 'Sommerferien', false);
        $this->lateShiftThenEarlyShift($user, $wednesday);

        $violations = $this->check->check($this->rule(11.0), $user, $wednesday->copy(), $wednesday->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function ordinary_weekday_does_not_trigger(): void
    {
        $user = User::factory()->create();
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);
        $this->lateShiftThenEarlyShift($user, $wednesday);

        $violations = $this->check->check($this->rule(11.0), $user, $wednesday->copy(), $wednesday->copy());

        $this->assertCount(0, $violations);
    }
}
