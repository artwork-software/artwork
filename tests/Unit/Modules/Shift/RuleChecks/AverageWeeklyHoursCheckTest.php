<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\AverageWeeklyHoursCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Durchschnittliche Wochenstunden über einen rollierenden Ausgleichszeitraum (period_weeks):
 * ein Verstoß je Woche an der letzten Schicht der Woche, wenn der Durchschnitt über dem Wert liegt.
 */
final class AverageWeeklyHoursCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private AverageWeeklyHoursCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new AverageWeeklyHoursCheck();
    }

    private function rule(float $maxAverage = 40.0, ?int $periodWeeks = 4): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'averageWeeklyHours',
            'individual_number_value' => $maxAverage,
            'period_weeks' => $periodWeeks,
            'is_active' => true,
        ]);
    }

    /**
     * Mo–Fr je $hours Stunden in der Woche ab $monday; liefert die Freitagsschicht.
     */
    private function workWeek(User $user, Carbon $monday, int $hours = 10): \Artwork\Modules\Shift\Models\Shift
    {
        $shift = null;
        for ($i = 0; $i < 5; $i++) {
            $shift = $this->shiftFor($user, $monday->copy()->addDays($i), '08:00:00', sprintf('%02d:00:00', 8 + $hours));
        }

        return $shift;
    }

    #[Test]
    public function violation_on_the_last_shift_of_the_week_when_the_rolling_average_exceeds_the_maximum(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // 4 Wochen à 50 h -> Ø 50 h > 40 h
        for ($week = 0; $week < 4; $week++) {
            $friday = $this->workWeek($user, $monday->copy()->addWeeks($week));
        }
        $week4Monday = $monday->copy()->addWeeks(3);

        $violations = $this->check->check($this->rule(40.0, 4), $user, $week4Monday->copy(), $week4Monday->copy()->addDays(6));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($friday->id, $violation->shift_id);
        $this->assertSame($week4Monday->copy()->addDays(4)->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(50.0, $violation->violation_data['average_hours'], 0.01);
        $this->assertEqualsWithDelta(40.0, $violation->violation_data['max_allowed'], 0.01);
        $this->assertSame(4, $violation->violation_data['period_weeks']);
        $this->assertSame($monday->toDateString(), $violation->violation_data['window_start']);
    }

    #[Test]
    public function no_violation_when_the_average_stays_within_the_maximum(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // 2 Wochen à 50 h + 2 Wochen frei -> Ø 25 h <= 40 h
        $this->workWeek($user, $monday->copy());
        $this->workWeek($user, $monday->copy()->addWeeks(3));
        $week4Monday = $monday->copy()->addWeeks(3);

        $violations = $this->check->check($this->rule(40.0, 4), $user, $week4Monday->copy(), $week4Monday->copy()->addDays(6));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function weeks_whose_window_has_less_than_half_of_its_weeks_planned_are_not_reported(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        $week4Monday = $monday->copy()->addWeeks(3);
        // Nur die geprüfte Woche hat Arbeit (50 h): Ø 12,5 h > 10 h, aber nur 1 von 4 Wochen mit Daten
        $this->workWeek($user, $week4Monday->copy());

        $violations = $this->check->check($this->rule(10.0, 4), $user, $week4Monday->copy(), $week4Monday->copy()->addDays(6));
        $this->assertCount(0, $violations);

        // Zweite Woche mit Daten -> Hälfte erreicht, Ø 25 h > 10 h -> Verstoß
        $this->workWeek($user, $monday->copy()->addWeeks(2));

        $violations = $this->check->check($this->rule(10.0, 4), $user, $week4Monday->copy(), $week4Monday->copy()->addDays(6));
        $this->assertCount(1, $violations);
        $this->assertSame(2, $violations->first()->violation_data['weeks_with_data']);
    }

    #[Test]
    public function individual_times_count_and_produce_a_violation_without_shift(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // 4 Wochen Mo–Fr je 10 h als individuelle Zeit (netto: 11 h minus 60 min Pause)
        for ($week = 0; $week < 4; $week++) {
            for ($i = 0; $i < 5; $i++) {
                $this->individualTimeFor($user, $monday->copy()->addWeeks($week)->addDays($i), '08:00', '19:00', 60);
            }
        }
        $week4Monday = $monday->copy()->addWeeks(3);

        $violations = $this->check->check($this->rule(40.0, 4), $user, $week4Monday->copy(), $week4Monday->copy()->addDays(6));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertNull($violation->shift_id);
        $this->assertSame($week4Monday->copy()->addDays(4)->toDateString(), $violation->violation_date->toDateString());
        $this->assertEqualsWithDelta(50.0, $violation->violation_data['average_hours'], 0.01);
    }

    #[Test]
    public function defaults_apply_without_value_and_period(): void
    {
        $rule = $this->rule(0.0, null);

        $this->assertSame(AverageWeeklyHoursCheck::DEFAULT_PERIOD_WEEKS, AverageWeeklyHoursCheck::periodWeeksFor($rule));
        $this->assertSame(24, AverageWeeklyHoursCheck::DEFAULT_PERIOD_WEEKS);
        $this->assertEqualsWithDelta(48.0, AverageWeeklyHoursCheck::DEFAULT_MAX_AVERAGE_HOURS, 0.001);

        // Wert/Zeitraum leer: 4 Wochen à 50 h reißen 48 h nicht über 24 Wochen (Ø ~8,3 h) -> kein Verstoß
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        for ($week = 0; $week < 4; $week++) {
            $this->workWeek($user, $monday->copy()->addWeeks($week));
        }
        $week4Monday = $monday->copy()->addWeeks(3);

        $this->assertCount(0, $this->check->check($rule, $user, $week4Monday->copy(), $week4Monday->copy()->addDays(6)));
    }

    #[Test]
    public function covered_range_spans_full_calendar_weeks(): void
    {
        $wednesday = $this->futureWeekday(Carbon::WEDNESDAY);

        [$from, $to] = $this->check->getCoveredRange($this->rule(), $wednesday->copy(), $wednesday->copy()->addDay());

        $this->assertTrue($from->isMonday());
        $this->assertTrue($to->isSunday());
        $this->assertSame($wednesday->copy()->startOfWeek(Carbon::MONDAY)->toDateString(), $from->toDateString());
    }
}
