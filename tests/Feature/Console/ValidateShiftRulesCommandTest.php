<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Shift\Console\Commands\ValidateShiftRulesCommand;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\Feature\FeatureTestCase;

final class ValidateShiftRulesCommandTest extends FeatureTestCase
{
    use CreatesShiftRuleFixtures;

    /**
     * Command isoliert ausführen (ohne den kompletten Console-Kernel zu booten).
     */
    private function runCommand(): int
    {
        $command = $this->app->make(ValidateShiftRulesCommand::class);
        $command->setLaravel($this->app);

        $tester = new CommandTester($command);
        $tester->execute(['--days' => 14]);

        return $tester->getStatusCode();
    }

    private function resolvedViolationWithOverdueDayOff(): ShiftRuleViolation
    {
        $user = User::factory()->create();
        $rule = ShiftRule::factory()->create(['trigger_type' => 'maxWorkingHoursOnDay', 'individual_number_value' => 8.0]);

        $violation = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => null,
            'violation_date' => Carbon::now()->subDays(40)->toDateString(),
            'status' => 'resolved',
            'resolved_at' => Carbon::now()->subDays(39),
            'compensation_days' => 1.0,
            'compensation_deadline' => Carbon::now()->subDay()->toDateString(),
        ]);

        CompensationDayOff::create([
            'user_id' => $user->id,
            'violation_id' => $violation->id,
            'value' => 1.0,
            'deadline' => Carbon::now()->subDay()->toDateString(),
            'granted_at' => null,
        ]);

        return $violation;
    }

    #[Test]
    public function overdue_compensation_day_creates_a_follow_up_violation_with_severity_error(): void
    {
        $violation = $this->resolvedViolationWithOverdueDayOff();

        $this->assertSame(0, $this->runCommand());

        $child = ShiftRuleViolation::where('parent_violation_id', $violation->id)->first();
        $this->assertNotNull($child);
        $this->assertSame('error', $child->severity);
        $this->assertSame('active', $child->status);
        $this->assertFalse((bool) $child->is_manual);
        $this->assertSame($violation->user_id, $child->user_id);
        $this->assertSame('compensation_deadline_expired', $child->violation_data['type']);
    }

    #[Test]
    public function second_run_does_not_duplicate_the_follow_up_violation(): void
    {
        $violation = $this->resolvedViolationWithOverdueDayOff();

        $this->runCommand();
        $this->runCommand();

        $this->assertSame(1, ShiftRuleViolation::where('parent_violation_id', $violation->id)->count());
    }

    #[Test]
    public function follow_up_violation_survives_the_automatic_cleanup_of_a_later_run(): void
    {
        $violation = $this->resolvedViolationWithOverdueDayOff();
        $this->runCommand();
        $child = ShiftRuleViolation::where('parent_violation_id', $violation->id)->firstOrFail();

        // Folge-Verstoß liegt (Frist gestern) außerhalb des Prüffensters heute..+14 — und wäre selbst
        // innerhalb durch parent_violation_id vom Aufräumen ausgenommen.
        $this->runCommand();

        $this->assertDatabaseHas('shift_rule_violations', ['id' => $child->id, 'status' => 'active']);
    }

    #[Test]
    public function granted_compensation_day_is_not_overdue(): void
    {
        $violation = $this->resolvedViolationWithOverdueDayOff();
        CompensationDayOff::where('violation_id', $violation->id)->update([
            'granted_at' => Carbon::now(),
            'granted_date' => Carbon::now()->toDateString(),
        ]);

        $this->runCommand();

        $this->assertSame(0, ShiftRuleViolation::where('parent_violation_id', $violation->id)->count());
    }
}
