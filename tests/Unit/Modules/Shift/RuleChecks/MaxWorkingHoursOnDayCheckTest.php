<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\MaxWorkingHoursOnDayCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

final class MaxWorkingHoursOnDayCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private MaxWorkingHoursOnDayCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new MaxWorkingHoursOnDayCheck();
    }

    private function rule(float $hours = 8.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => $hours,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function creates_violation_when_planned_hours_exceed_maximum(): void
    {
        $user = User::factory()->create();
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $date, '08:00:00', '18:00:00'); // 10 h

        $violations = $this->check->check($this->rule(8.0), $user, $date->copy(), $date->copy());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($date->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(10.0, $violation->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(8.0, $violation->violation_data['max_allowed'], 0.01);
        $this->assertSame('active', $violation->status);
        $this->assertFalse((bool) $violation->is_manual);
    }

    #[Test]
    public function break_minutes_reduce_planned_hours(): void
    {
        $user = User::factory()->create();
        $date = $this->futureWeekday(Carbon::TUESDAY);
        // 9 h brutto, 60 min Pause = 8 h netto -> keine Überschreitung
        $this->shiftFor($user, $date, '08:00:00', '17:00:00', ['break_minutes' => 60]);

        $violations = $this->check->check($this->rule(8.0), $user, $date->copy(), $date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function no_violation_when_within_maximum(): void
    {
        $user = User::factory()->create();
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $date, '08:00:00', '15:00:00'); // 7 h

        $violations = $this->check->check($this->rule(8.0), $user, $date->copy(), $date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function rerun_updates_existing_violation_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $date, '08:00:00', '18:00:00');
        $rule = $this->rule(8.0);

        $first = $this->check->check($rule, $user, $date->copy(), $date->copy())->first();
        $second = $this->check->check($rule, $user, $date->copy(), $date->copy())->first();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, \Artwork\Modules\Shift\Models\ShiftRuleViolation::where('user_id', $user->id)->count());
    }

    #[Test]
    public function shift_and_individual_time_are_summed_for_the_daily_maximum(): void
    {
        $user = User::factory()->create();
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $date, '08:00:00', '13:00:00'); // 5 h Schicht
        $user->individualTimes()->create([
            'title' => 'Büro',
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'start_time' => '14:00',
            'end_time' => '20:00', // 6 h individuelle Zeit
            'full_day' => false,
            'working_time_minutes' => 360,
            'break_minutes' => 0,
        ]);

        $violations = $this->check->check($this->rule(10.0), $user, $date->copy(), $date->copy());

        $this->assertCount(1, $violations);
        $this->assertEqualsWithDelta(11.0, $violations->first()->violation_data['planned_hours'], 0.01);
        $this->assertNotNull($violations->first()->shift_id);
    }

    #[Test]
    public function individual_times_alone_can_exceed_the_daily_maximum(): void
    {
        $user = User::factory()->create();
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $user->individualTimes()->create([
            'title' => 'Büro',
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'start_time' => '08:00',
            'end_time' => '19:30', // 11,5 h brutto
            'full_day' => false,
            'working_time_minutes' => 660,
            'break_minutes' => 30, // 11 h netto
        ]);

        $rule = $this->rule(10.0);
        $violations = $this->check->check($rule, $user, $date->copy(), $date->copy());

        $this->assertCount(1, $violations);
        $this->assertNull($violations->first()->shift_id);
        $this->assertEqualsWithDelta(11.0, $violations->first()->violation_data['planned_hours'], 0.01);

        // Zweiter Lauf erzeugt kein Duplikat
        $this->check->check($rule, $user, $date->copy(), $date->copy());
        $this->assertSame(
            1,
            \Artwork\Modules\Shift\Models\ShiftRuleViolation::where('user_id', $user->id)->count()
        );
    }

    #[Test]
    public function individual_shift_times_of_the_person_override_the_shift_times(): void
    {
        $user = User::factory()->create();
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $shift = $this->shiftFor($user, $date, '08:00:00', '12:00:00'); // Schicht selbst nur 4 h
        // Person hat abweichende Zeiten: 08:00-20:00 = 12 h
        $shift->users()->updateExistingPivot($user->id, [
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
            'start_time' => '08:00',
            'end_time' => '20:00',
        ]);

        $violations = $this->check->check($this->rule(10.0), $user, $date->copy(), $date->copy());

        $this->assertCount(1, $violations);
        $this->assertEqualsWithDelta(12.0, $violations->first()->violation_data['planned_hours'], 0.01);
    }
}
