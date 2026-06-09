<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\HalfDayOffConflictCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HalfDayOffConflictCheckTest extends TestCase
{
    private HalfDayOffConflictCheck $check;
    private Carbon $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new HalfDayOffConflictCheck();
        $this->date = Carbon::now()->startOfDay()->addDays(5);
    }

    private function rule(): ShiftRule
    {
        // Threshold T = 14:00 encoded as 14.0.
        return ShiftRule::factory()->create([
            'trigger_type' => 'halfDayOffConflict',
            'individual_number_value' => 14.0,
            'is_active' => true,
        ]);
    }

    private function grantHalf(User $user, string $period): void
    {
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 0.5,
            'half_day_period' => $period,
            'granted_date' => $this->date->toDateString(),
            'granted_at' => Carbon::now(),
            'deadline' => $this->date->copy()->addDays(30)->toDateString(),
        ]);
    }

    private function shift(User $user, string $start, string $end): void
    {
        $shift = Shift::factory()->create([
            'start_date' => $this->date->toDateString(),
            'end_date' => $this->date->toDateString(),
            'start' => $start,
            'end' => $end,
            'shift_group_id' => null,
        ]);

        $sq = ShiftQualification::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $sq->id,
            'shift_count' => 1,
        ]);
    }

    #[Test]
    public function morning_off_with_shift_starting_before_threshold_is_a_violation(): void
    {
        $user = User::factory()->create();
        $this->grantHalf($user, 'morning');
        $this->shift($user, '08:00:00', '16:00:00'); // starts 08:00 < 14:00

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function morning_off_with_shift_starting_after_threshold_is_ok(): void
    {
        $user = User::factory()->create();
        $this->grantHalf($user, 'morning');
        $this->shift($user, '15:00:00', '18:00:00'); // starts 15:00 >= 14:00

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function afternoon_off_with_shift_ending_after_threshold_is_a_violation(): void
    {
        $user = User::factory()->create();
        $this->grantHalf($user, 'afternoon');
        $this->shift($user, '10:00:00', '17:00:00'); // ends 17:00 > 14:00

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function afternoon_off_with_shift_ending_before_threshold_is_ok(): void
    {
        $user = User::factory()->create();
        $this->grantHalf($user, 'afternoon');
        $this->shift($user, '08:00:00', '12:00:00'); // ends 12:00 <= 14:00

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function whole_day_off_with_any_shift_is_a_violation(): void
    {
        $user = User::factory()->create();
        $this->grantHalf($user, 'morning');
        $this->grantHalf($user, 'afternoon');
        $this->shift($user, '15:00:00', '16:00:00'); // time irrelevant for the both-halves case

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function no_violation_when_user_has_no_shift_that_day(): void
    {
        $user = User::factory()->create();
        $this->grantHalf($user, 'morning');
        // no shift

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(0, $violations);
    }
}
