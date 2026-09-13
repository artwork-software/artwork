<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\WorkOnSundayCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

final class WorkOnSundayCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private WorkOnSundayCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new WorkOnSundayCheck();
    }

    private function rule(): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'workOnSunday',
            'individual_number_value' => 0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function shift_on_sunday_creates_a_violation(): void
    {
        $user = User::factory()->create();
        $sunday = $this->futureWeekday(Carbon::SUNDAY);
        $this->shiftFor($user, $sunday);

        $violations = $this->check->check($this->rule(), $user, $sunday->copy()->subDays(6), $sunday->copy());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($sunday->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame('warning', $violation->severity);
        $this->assertSame('sunday', $violation->violation_data['weekday']);
    }

    #[Test]
    public function shift_on_a_weekday_does_not_trigger(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        $this->shiftFor($user, $monday);

        $violations = $this->check->check($this->rule(), $user, $monday->copy(), $monday->copy()->addDays(6));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function shift_past_midnight_counts_on_its_start_day_only(): void
    {
        $user = User::factory()->create();
        $sunday = $this->futureWeekday(Carbon::SUNDAY);
        // Sa 22:00 -> So 02:00: Starttag Samstag, kein Sonntagsverstoß
        $this->shiftFor($user, $sunday->copy()->subDay(), '22:00:00', '02:00:00', [], $sunday->copy());
        // So 22:00 -> Mo 02:00: Starttag Sonntag -> Verstoß am Sonntag
        $this->shiftFor($user, $sunday->copy(), '22:00:00', '02:00:00', [], $sunday->copy()->addDay());

        $violations = $this->check->check($this->rule(), $user, $sunday->copy()->subDay(), $sunday->copy()->addDay());

        $this->assertCount(1, $violations);
        $this->assertSame($sunday->toDateString(), $violations->first()->violation_date->toDateString());
    }
}
