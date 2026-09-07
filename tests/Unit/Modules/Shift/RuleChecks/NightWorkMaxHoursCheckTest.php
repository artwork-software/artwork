<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\NightWorkMaxHoursCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Nachtarbeit-Tagesmaximum: Kalendertage mit mindestens 2 h im Nachtfenster (22:00–06:00) dürfen
 * insgesamt höchstens Wert Stunden Arbeit haben (Netto, Tagesgrenze).
 */
final class NightWorkMaxHoursCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private NightWorkMaxHoursCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new NightWorkMaxHoursCheck();
        $this->configureNight('22:00', '06:00');
    }

    private function configureNight(string $start, string $end): void
    {
        $settings = app(GeneralSettings::class);
        $settings->start_night_time = $start;
        $settings->end_night_time = $end;
        $settings->save();
    }

    private function rule(float $maxHours = 8.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'nightWorkMaxHours',
            'individual_number_value' => $maxHours,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function violation_on_a_day_with_two_night_hours_and_more_than_the_maximum(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // 13:00–01:00: am Tag selbst 11 h, davon 22:00–24:00 = 2 h Nacht
        $shift = $this->shiftFor($user, $day, '13:00:00', '01:00:00', [], $day->copy()->addDay());

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($shift->id, $violation->shift_id);
        $this->assertSame($day->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(11.0, $violation->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(2.0, $violation->violation_data['night_hours'], 0.01);
        $this->assertEqualsWithDelta(8.0, $violation->violation_data['max_allowed'], 0.01);
        $this->assertSame('22:00–06:00', $violation->violation_data['night_window']);
    }

    #[Test]
    public function no_violation_with_less_than_two_night_hours_even_if_the_day_is_long(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // 12:00–23:00: 11 h, aber nur 1 h Nacht -> diese Regel greift nicht (Tagesmaximum ist eine andere Regel)
        $this->shiftFor($user, $day, '12:00:00', '23:00:00');

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function no_violation_when_the_night_day_stays_within_the_maximum(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // 16:00–00:00: genau 8 h mit 2 h Nacht -> nicht über dem Maximum
        $this->shiftFor($user, $day, '16:00:00', '00:00:00', [], $day->copy()->addDay());
        // Folgetag 20:00–04:00: 4 h am Tag (davon 2 h Nacht) + 4 h am Folgetag (4 h Nacht) -> je Tag <= 8 h
        $this->shiftFor($user, $day->copy()->addDays(2), '20:00:00', '04:00:00', [], $day->copy()->addDays(3));

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy()->addDays(3));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function early_morning_night_hours_count_for_the_following_calendar_day(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // 21:00–06:00 (9 h): Tag 1 = 3 h (1 h Nacht), Tag 2 = 6 h (6 h Nacht); plus Tag 2 08:00–11:00 -> Tag 2 = 9 h > 8 h
        $this->shiftFor($user, $day, '21:00:00', '06:00:00', [], $day->copy()->addDay());
        $this->shiftFor($user, $day->copy()->addDay(), '08:00:00', '11:00:00');

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy()->addDay());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($day->copy()->addDay()->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(9.0, $violation->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(6.0, $violation->violation_data['night_hours'], 0.01);
    }

    #[Test]
    public function pivot_times_of_the_person_take_precedence_over_the_shift_times(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // Schicht 13:00–01:00 (würde reißen), Person nur 13:00–20:00 -> keine Nacht, kein Verstoß
        $this->shiftWithPivotTimesFor($user, $day, '13:00:00', '01:00:00', '13:00:00', '20:00:00');

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy()->addDay());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function without_rule_value_the_default_of_eight_hours_applies(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $day, '13:00:00', '01:00:00', [], $day->copy()->addDay());

        $violations = $this->check->check($this->rule(0.0), $user, $day->copy(), $day->copy());

        $this->assertCount(1, $violations);
        $this->assertEqualsWithDelta(8.0, $violations->first()->violation_data['max_allowed'], 0.01);
    }
}
