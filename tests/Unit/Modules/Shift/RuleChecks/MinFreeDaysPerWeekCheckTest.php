<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\MinFreeDaysPerWeekCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Mindestens X ganze freie Tage pro Woche (NV Bühne): Verstoß ohne Schicht auf den Sonntag der Woche,
 * wenn die Woche weniger als X Tage ohne Schicht und ohne individuelle Zeit hat.
 */
final class MinFreeDaysPerWeekCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private MinFreeDaysPerWeekCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new MinFreeDaysPerWeekCheck();
    }

    private function rule(float $target = 2.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'minFreeDaysPerWeek',
            'individual_number_value' => $target,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function violation_on_the_sunday_when_the_week_has_too_few_free_days(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Mo–Sa Schicht -> nur der Sonntag frei (1 < 2)
        for ($i = 0; $i < 6; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }

        $violations = $this->check->check($this->rule(2.0), $user, $monday->copy()->addDays(2), $monday->copy()->addDays(3));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertNull($violation->shift_id);
        $this->assertSame($monday->copy()->addDays(6)->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame(1, $violation->violation_data['free_days']);
        $this->assertSame(2, $violation->violation_data['target']);
        $this->assertSame($monday->isoWeek(), $violation->violation_data['week']);
        $this->assertSame('warning', $violation->severity);
    }

    #[Test]
    public function no_violation_with_enough_free_days(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Mo–Fr Schicht -> Sa + So frei (2 >= 2)
        for ($i = 0; $i < 5; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }

        $violations = $this->check->check($this->rule(2.0), $user, $monday->copy(), $monday->copy()->addDays(6));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function a_shift_past_midnight_occupies_both_days_and_individual_times_count(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Mo–Do Schicht, Fr individuelle Zeit, Sa 22:00–02:00 belegt Sa UND So -> 0 freie Tage
        for ($i = 0; $i < 4; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }
        $this->individualTimeFor($user, $monday->copy()->addDays(4));
        $saturday = $monday->copy()->addDays(5);
        $this->shiftFor($user, $saturday, '22:00:00', '02:00:00', [], $saturday->copy()->addDay());

        $violations = $this->check->check($this->rule(2.0), $user, $monday->copy(), $monday->copy()->addDays(6));

        $this->assertCount(1, $violations);
        $this->assertSame(0, $violations->first()->violation_data['free_days']);
    }

    #[Test]
    public function without_rule_value_the_contract_value_is_used(): void
    {
        [$user] = $this->userWithContract(
            ['free_full_days_per_week' => 2],
            ['free_full_days_per_week' => 2]
        );
        $monday = $this->futureWeekday(Carbon::MONDAY);
        for ($i = 0; $i < 6; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }

        $violations = $this->check->check($this->rule(0.0), $user, $monday->copy(), $monday->copy()->addDays(6));

        $this->assertCount(1, $violations);
        $this->assertSame(2, $violations->first()->violation_data['target']);
    }

    #[Test]
    public function a_zero_on_the_assignment_falls_back_to_the_template_value(): void
    {
        // Zuweisung 0 (NOT NULL DEFAULT 0 = nicht gesetzt), Vorlage 2 → Ziel 2
        [$user] = $this->userWithContract(
            ['free_full_days_per_week' => 2],
            ['free_full_days_per_week' => 0]
        );
        $monday = $this->futureWeekday(Carbon::MONDAY);
        for ($i = 0; $i < 6; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }

        $violations = $this->check->check($this->rule(0.0), $user, $monday->copy(), $monday->copy()->addDays(6));

        $this->assertCount(1, $violations);
        $this->assertSame(2, $violations->first()->violation_data['target']);
    }

    #[Test]
    public function without_rule_value_and_without_contract_value_the_rule_does_not_apply(): void
    {
        [$user] = $this->userWithContract(
            ['free_full_days_per_week' => 0],
            ['free_full_days_per_week' => 0]
        );
        $monday = $this->futureWeekday(Carbon::MONDAY);
        for ($i = 0; $i < 7; $i++) {
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }

        $violations = $this->check->check($this->rule(0.0), $user, $monday->copy(), $monday->copy()->addDays(6));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function one_violation_per_week_and_covered_range_spans_full_weeks(): void
    {
        $user = User::factory()->create();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        // Zwei Wochen Mo–Sa
        for ($i = 0; $i < 13; $i++) {
            if ($monday->copy()->addDays($i)->isSunday()) {
                continue;
            }
            $this->shiftFor($user, $monday->copy()->addDays($i));
        }

        $violations = $this->check->check($this->rule(2.0), $user, $monday->copy()->addDays(3), $monday->copy()->addDays(8));

        $this->assertCount(2, $violations);
        [$from, $to] = $this->check->getCoveredRange($this->rule(), $monday->copy()->addDays(3), $monday->copy()->addDays(8));
        $this->assertSame($monday->toDateString(), $from->toDateString());
        $this->assertSame($monday->copy()->addDays(13)->toDateString(), $to->toDateString());
    }
}
