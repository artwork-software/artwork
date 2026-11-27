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

    private function ruleWith(array $config): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'halfDayOffOnSpecialDay',
            'individual_number_value' => 0,
            'config' => $config,
            'is_active' => true,
        ]);
    }

    private function grantedHalfOn(User $user, Carbon $date, string $period): void
    {
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 0.5,
            'half_day_period' => $period,
            'granted_date' => $date->toDateString(),
            'granted_at' => Carbon::now(),
            'deadline' => $date->copy()->addDays(30)->toDateString(),
        ]);
    }

    #[Test]
    public function ordinary_sunday_only_counts_with_include_sundays(): void
    {
        $user = User::factory()->create();
        $sunday = Carbon::now()->startOfDay()->addWeeks(2)->next(Carbon::SUNDAY);
        $this->grantedHalfOn($user, $sunday, 'morning');

        // Ohne config: bisheriges Verhalten, ein gewöhnlicher Sonntag ist kein Sondertag
        $this->assertCount(0, $this->check->check($this->rule(), $user, $sunday->copy(), $sunday->copy()));

        $violations = $this->check->check(
            $this->ruleWith(['include_sundays' => true]),
            $user,
            $sunday->copy(),
            $sunday->copy()
        );
        $this->assertCount(1, $violations);
        $this->assertTrue($violations->first()->violation_data['on_sunday']);
    }

    #[Test]
    public function morning_only_ignores_a_free_afternoon(): void
    {
        $user = User::factory()->create();
        $this->specialDay();
        $this->grantedHalfOn($user, $this->date, 'afternoon');

        // Ohne config zählt jeder halbe freie Tag am Sondertag
        $this->assertCount(1, $this->check->check($this->rule(), $user, $this->date->copy(), $this->date->copy()));
        $this->assertCount(0, $this->check->check(
            $this->ruleWith(['morning_only' => true]),
            $user,
            $this->date->copy(),
            $this->date->copy()
        ));

        $this->grantedHalfOn($user, $this->date, 'morning');
        $this->assertCount(1, $this->check->check(
            $this->ruleWith(['morning_only' => true]),
            $user,
            $this->date->copy(),
            $this->date->copy()
        ));
    }
}
