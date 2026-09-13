<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\WeeklyMaxHoursCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

final class WeeklyMaxHoursCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private WeeklyMaxHoursCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new WeeklyMaxHoursCheck();
    }

    private function rule(float $hours = 20.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'weeklyMaxHours',
            'individual_number_value' => $hours,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function violation_on_the_day_the_weekly_hours_exceed_the_maximum(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Mo 8 h, Di 8 h (16 h), Mi 8 h (24 h > 20 h)
        for ($i = 0; $i < 3; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i), '08:00:00', '16:00:00');
        }

        $violations = $this->check->check($this->rule(20.0), $user, $monday->copy(), $monday->copy()->addDays(2));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($monday->copy()->addDays(2)->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(24.0, $violation->violation_data['weekly_hours'], 0.01);
        $this->assertEqualsWithDelta(20.0, $violation->violation_data['max_allowed'], 0.01);
    }

    #[Test]
    public function hours_of_the_previous_week_do_not_count(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Vorwoche: Fr + Sa je 8 h — neue Woche beginnt am Montag bei 0
        $this->shiftFor($user, $monday->copy()->subDays(3), '08:00:00', '16:00:00');
        $this->shiftFor($user, $monday->copy()->subDays(2), '08:00:00', '16:00:00');
        // Diese Woche: Mo + Di je 8 h = 16 h < 20 h
        $this->shiftFor($user, $monday->copy(), '08:00:00', '16:00:00');
        $this->shiftFor($user, $monday->copy()->addDay(), '08:00:00', '16:00:00');

        $violations = $this->check->check($this->rule(20.0), $user, $monday->copy(), $monday->copy()->addDay());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function individual_times_count_towards_the_weekly_maximum_even_without_shifts(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        foreach ([0, 1, 2] as $offset) {
            $day = $monday->copy()->addDays($offset);
            $user->individualTimes()->create([
                'title' => 'Büro',
                'start_date' => $day->toDateString(),
                'end_date' => $day->toDateString(),
                'start_time' => '08:00',
                'end_time' => '16:00', // 8 h je Tag
                'full_day' => false,
                'working_time_minutes' => 480,
                'break_minutes' => 0,
            ]);
        }

        // 24 h in der Woche, Maximum 20 h -> Verstoss ab Mittwoch, ohne Schichtbezug
        $violations = $this->check->check($this->rule(20.0), $user, $monday->copy(), $monday->copy()->addDays(6));

        $this->assertGreaterThanOrEqual(1, $violations->count());
        $first = $violations->first();
        $this->assertNull($first->shift_id);
        $this->assertSame($monday->copy()->addDays(2)->toDateString(), $first->violation_date->toDateString());
        $this->assertEqualsWithDelta(24.0, $first->violation_data['weekly_hours'], 0.01);
    }
}
