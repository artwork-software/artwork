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
 * Nachtarbeit-Tagesmaximum: Schichten werden ihrem Starttag zugerechnet (auch über Mitternacht),
 * individuelle Zeiten je Kalendertag zugeschnitten (wie die nächtliche Buchung); hat ein Tag mindestens
 * 2 h im Nachtfenster (22:00–06:00), darf die Arbeit dieses Tages netto höchstens Wert Stunden betragen –
 * eine einzelne Schicht 20:00–06:00 (10 h) reißt die Grenze also allein.
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
        // 13:00–01:00: 12 h ganz dem Starttag zugerechnet, davon 22:00–01:00 = 3 h Nacht
        $shift = $this->shiftFor($user, $day, '13:00:00', '01:00:00', [], $day->copy()->addDay());

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy()->addDay());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($shift->id, $violation->shift_id);
        $this->assertSame($day->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(12.0, $violation->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(3.0, $violation->violation_data['night_hours'], 0.01);
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
    public function a_single_night_shift_longer_than_the_maximum_is_a_violation(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // 20:00–06:00 = 10 h netto in EINER Schicht (8 h Nacht) – vorher durch den Tagesschnitt
        // (4 h + 6 h) unsichtbar
        $shift = $this->shiftFor($user, $day, '20:00:00', '06:00:00', [], $day->copy()->addDay());

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy()->addDay());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($shift->id, $violation->shift_id);
        $this->assertSame($day->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(10.0, $violation->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(8.0, $violation->violation_data['night_hours'], 0.01);
    }

    #[Test]
    public function the_break_is_deducted_from_the_net_length_of_a_night_shift(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // 20:00–05:00 = 9 h brutto, 60 min Pause → 8 h netto → kein Verstoß
        $this->shiftFor($user, $day, '20:00:00', '05:00:00', ['break_minutes' => 60], $day->copy()->addDay());

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy()->addDay());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function a_shift_past_midnight_is_attributed_to_its_start_day(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // 21:00–06:00 (9 h, 8 h Nacht) zählt ganz zu Tag 1 → Tag 1 = 9 h > 8 h;
        // Tag 2 08:00–11:00 (3 h, keine Nacht) bleibt für sich unter der Schwelle
        $this->shiftFor($user, $day, '21:00:00', '06:00:00', [], $day->copy()->addDay());
        $this->shiftFor($user, $day->copy()->addDay(), '08:00:00', '11:00:00');

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy()->addDay());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($day->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(9.0, $violation->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(8.0, $violation->violation_data['night_hours'], 0.01);
    }

    #[Test]
    public function several_shifts_starting_on_the_same_day_are_summed(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        // 06:00–12:00 (6 h, keine Nacht) + 21:00–00:00 (3 h, 2 h Nacht) → Tag 9 h mit 2 h Nacht → Verstoß
        $this->shiftFor($user, $day, '06:00:00', '12:00:00');
        $this->shiftFor($user, $day, '21:00:00', '00:00:00', [], $day->copy()->addDay());

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $day->copy()->addDay());

        $this->assertCount(1, $violations);
        $this->assertEqualsWithDelta(9.0, $violations->first()->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(2.0, $violations->first()->violation_data['night_hours'], 0.01);
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

    /**
     * Individuelle Zeiten werden — wie in der nächtlichen Buchung (WorkTimeBookingNightMinutesTest) — je
     * Kalendertag zugeschnitten: 23:00–03:00 zählt 1 h Nacht am Starttag und 3 h am Folgetag. Mit der
     * früheren Starttag-Zuordnung (4 h Nacht + 4 h netto am Tag 1) läge der Verstoß am falschen Tag.
     */
    #[Test]
    public function an_individual_time_across_midnight_is_split_per_calendar_day_like_the_booking(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        $nextDay = $day->copy()->addDay();
        // Tag 1: Schicht 09:00–17:00 (8 h, keine Nacht) + Anteil der Individualzeit 23:00–24:00 (1 h, 1 h Nacht)
        //        → 9 h netto, aber nur 60 min Nacht → keine Nachtarbeit im Sinne der Regel
        // Tag 2: Anteil 00:00–03:00 (3 h, 180 min Nacht) + Schicht 07:00–14:00 (7 h) → 10 h netto mit 3 h Nacht → Verstoß
        $this->shiftFor($user, $day, '09:00:00', '17:00:00');
        $this->individualTimeFor($user, $day, '23:00', '03:00', 0, $nextDay);
        $nextDayShift = $this->shiftFor($user, $nextDay, '07:00:00', '14:00:00');

        $violations = $this->check->check($this->rule(8.0), $user, $day->copy(), $nextDay->copy());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($nextDay->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame($nextDayShift->id, $violation->shift_id);
        $this->assertEqualsWithDelta(10.0, $violation->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(3.0, $violation->violation_data['night_hours'], 0.01);
    }

    #[Test]
    public function the_tail_of_an_individual_time_alone_can_violate_on_the_following_day(): void
    {
        $user = User::factory()->create();
        $day = $this->futureWeekday(Carbon::TUESDAY);
        $nextDay = $day->copy()->addDay();
        // 23:00–03:00 ohne Schicht: Tag 2 hat 3 h netto mit 3 h Nacht → bei Maximum 2 h ein Verstoß ohne Schicht;
        // Tag 1 (1 h, 60 min Nacht) liegt unter der 2-h-Nachtschwelle
        $this->individualTimeFor($user, $day, '23:00', '03:00', 0, $nextDay);

        $violations = $this->check->check($this->rule(2.0), $user, $day->copy(), $nextDay->copy());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertNull($violation->shift_id);
        $this->assertSame($nextDay->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(3.0, $violation->violation_data['planned_hours'], 0.01);
        $this->assertEqualsWithDelta(3.0, $violation->violation_data['night_hours'], 0.01);
    }
}
