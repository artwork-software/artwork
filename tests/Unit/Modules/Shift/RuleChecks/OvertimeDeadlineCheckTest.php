<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\OvertimeDeadlineCheck;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Überstunden-Abbaufrist: Verstoß ohne Schicht am Fristdatum, wenn die Frist innerhalb der
 * Vorwarnzeit liegt oder überschritten ist (dann severity error).
 */
final class OvertimeDeadlineCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private OvertimeDeadlineCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new OvertimeDeadlineCheck();
    }

    private function rule(int $warnDays = 14): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'overtimeDeadline',
            'individual_number_value' => $warnDays,
            'is_active' => true,
        ]);
    }

    private function userWithOvertimeRule(bool $active = true): array
    {
        return $this->userWithContract(
            ['overtime_rule_active' => $active, 'overtime_compensation_period' => 90],
            ['overtime_rule_active' => $active, 'overtime_compensation_period' => 90]
        );
    }

    #[Test]
    public function warns_n_days_before_the_deadline_with_the_remaining_minutes(): void
    {
        [$user] = $this->userWithOvertimeRule();
        $today = Carbon::today();
        $deadline = $today->copy()->addDays(10);
        $this->overtimeEntryFor($user, $today->copy()->subDays(80), 210, $deadline);

        $violations = $this->check->check($this->rule(14), $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertNull($violation->shift_id);
        $this->assertSame($deadline->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame('warning', $violation->severity);
        $this->assertSame(210, $violation->violation_data['remaining_minutes']);
        $this->assertSame($deadline->toDateString(), $violation->violation_data['deadline']);
        $this->assertSame(10, $violation->violation_data['days_left']);
        $this->assertSame($today->copy()->subDays(80)->toDateString(), $violation->violation_data['booking_day']);
    }

    #[Test]
    public function no_warning_when_the_deadline_is_further_away_than_the_warning_window(): void
    {
        [$user] = $this->userWithOvertimeRule();
        $today = Carbon::today();
        $this->overtimeEntryFor($user, $today->copy()->subDays(10), 120, $today->copy()->addDays(30));

        $violations = $this->check->check($this->rule(14), $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function expired_deadline_is_an_error(): void
    {
        [$user] = $this->userWithOvertimeRule();
        $today = Carbon::today();
        $deadline = $today->copy()->subDays(3);
        $this->overtimeEntryFor($user, $today->copy()->subDays(93), 90, $deadline, UserOvertime::STATUS_PAYABLE);

        $violations = $this->check->check($this->rule(14), $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame('error', $violation->severity);
        $this->assertSame(-3, $violation->violation_data['days_left']);
        $this->assertTrue($violation->violation_data['expired']);
    }

    #[Test]
    public function compensated_or_paid_out_overtime_does_not_warn(): void
    {
        [$user] = $this->userWithOvertimeRule();
        $today = Carbon::today();
        $this->overtimeEntryFor($user, $today->copy()->subDays(80), 210, $today->copy()->addDays(5), UserOvertime::STATUS_COMPENSATED, 0);
        $this->overtimeEntryFor($user, $today->copy()->subDays(70), 60, $today->copy()->subDays(1), UserOvertime::STATUS_PAID_OUT, 0);

        $violations = $this->check->check($this->rule(14), $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function contract_without_overtime_rule_does_not_warn(): void
    {
        [$user] = $this->userWithOvertimeRule(false);
        $today = Carbon::today();
        $this->overtimeEntryFor($user, $today->copy()->subDays(80), 210, $today->copy()->addDays(5));

        $violations = $this->check->check($this->rule(14), $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function rerun_updates_the_existing_violation_instead_of_duplicating_it(): void
    {
        [$user] = $this->userWithOvertimeRule();
        $today = Carbon::today();
        $entry = $this->overtimeEntryFor($user, $today->copy()->subDays(80), 210, $today->copy()->addDays(5));
        $rule = $this->rule(14);

        $this->check->check($rule, $user, $today->copy(), $today->copy()->addDays(14));
        $entry->update(['remaining_minutes' => 90]);
        $violations = $this->check->check($rule, $user, $today->copy(), $today->copy()->addDays(14));

        $this->assertCount(1, $violations);
        $this->assertSame(1, \Artwork\Modules\Shift\Models\ShiftRuleViolation::where('shift_rule_id', $rule->id)->count());
        $this->assertSame(90, $violations->first()->violation_data['remaining_minutes']);
    }
}
