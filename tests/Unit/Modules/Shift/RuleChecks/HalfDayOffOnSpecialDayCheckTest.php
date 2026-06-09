<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\HalfDayOffOnSpecialDayCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HalfDayOffOnSpecialDayCheckTest extends TestCase
{
    private HalfDayOffOnSpecialDayCheck $check;
    private Carbon $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new HalfDayOffOnSpecialDayCheck();
        $this->date = Carbon::now()->startOfDay()->addDays(10);
    }

    private function rule(): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'halfDayOffOnSpecialDay',
            'individual_number_value' => 1.0,
            'is_active' => true,
        ]);
    }

    private function specialDay(): void
    {
        Holiday::create([
            'name' => 'Special',
            'date' => $this->date->toDateString(),
            'end_date' => $this->date->toDateString(),
            'yearly' => false,
            'from_api' => false,
            'treatAsSpecialDay' => true,
        ]);
    }

    private function grantedHalf(User $user): void
    {
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 0.5,
            'half_day_period' => 'morning',
            'granted_date' => $this->date->toDateString(),
            'granted_at' => Carbon::now(),
            'deadline' => $this->date->copy()->addDays(30)->toDateString(),
        ]);
    }

    #[Test]
    public function creates_violation_when_half_day_granted_on_special_day(): void
    {
        $user = User::factory()->create();
        $this->specialDay();
        $this->grantedHalf($user);

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function no_violation_when_day_is_not_special(): void
    {
        $user = User::factory()->create();
        // no special-day holiday created
        $this->grantedHalf($user);

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function no_violation_when_no_half_day_granted(): void
    {
        $user = User::factory()->create();
        $this->specialDay();
        // no granted half

        $violations = $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy());

        $this->assertCount(0, $violations);
    }
}
