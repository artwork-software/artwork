<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\WorkOnHolidayCheck;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

final class WorkOnHolidayCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private WorkOnHolidayCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new WorkOnHolidayCheck();
    }

    private function rule(): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'workOnHoliday',
            'individual_number_value' => 0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function shift_on_flagged_holiday_creates_violation_with_entitlement(): void
    {
        [$user] = $this->userWithContract();
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);
        $this->holiday($wednesday, 'Tag der Arbeit', true);
        $this->shiftFor($user, $wednesday);

        $violations = $this->check->check($this->rule(), $user, $wednesday->copy()->subDays(2), $wednesday->copy()->addDays(2));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($wednesday->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame('warning', $violation->severity);
        $this->assertSame('Tag der Arbeit', $violation->violation_data['holiday_name']);
        $this->assertTrue($violation->violation_data['for_holiday']);
        $this->assertSame('replacement_rest_day', $violation->violation_data['entitlement']);
    }

    #[Test]
    public function school_holidays_without_flag_do_not_trigger(): void
    {
        [$user] = $this->userWithContract();
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);
        $this->holiday($wednesday, 'Herbstferien', false);
        $this->shiftFor($user, $wednesday);

        $violations = $this->check->check($this->rule(), $user, $wednesday->copy(), $wednesday->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function contract_switch_off_disables_special_days_for_the_person(): void
    {
        [$user] = $this->userWithContract(
            ['special_day_rule_active' => false],
            ['special_day_rule_active' => false]
        );
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);
        $this->holiday($wednesday, 'Tag der Arbeit', true);
        $this->shiftFor($user, $wednesday);

        $violations = $this->check->check($this->rule(), $user, $wednesday->copy(), $wednesday->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function plain_sunday_is_not_a_special_day_for_this_rule(): void
    {
        [$user] = $this->userWithContract();
        $sunday = $this->futureWeekday(Carbon::SUNDAY);
        $this->shiftFor($user, $sunday);

        $violations = $this->check->check($this->rule(), $user, $sunday->copy(), $sunday->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function no_violation_without_a_shift_on_the_holiday(): void
    {
        [$user] = $this->userWithContract();
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);
        $this->holiday($wednesday, 'Tag der Arbeit', true);

        $violations = $this->check->check($this->rule(), $user, $wednesday->copy(), $wednesday->copy());

        $this->assertCount(0, $violations);
    }
}
