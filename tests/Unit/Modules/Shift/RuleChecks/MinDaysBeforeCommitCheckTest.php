<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\MinDaysBeforeCommitCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

final class MinDaysBeforeCommitCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private MinDaysBeforeCommitCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new MinDaysBeforeCommitCheck();
    }

    private function rule(int $days = 14): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'minDaysBeforeCommit',
            'individual_number_value' => $days,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function uncommitted_shift_within_the_lead_time_is_a_violation(): void
    {
        $user = User::factory()->create();
        $date = Carbon::now()->startOfDay()->addDays(5);
        $this->shiftFor($user, $date, '08:00:00', '16:00:00', ['is_committed' => false]);

        $violations = $this->check->check($this->rule(14), $user, $date->copy(), $date->copy());

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertSame($date->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame(5, (int) $violation->violation_data['days_until_shift']);
        $this->assertSame(14, (int) $violation->violation_data['min_required']);
    }

    #[Test]
    public function committed_shift_is_fine(): void
    {
        $user = User::factory()->create();
        $date = Carbon::now()->startOfDay()->addDays(5);
        $this->shiftFor($user, $date, '08:00:00', '16:00:00', ['is_committed' => true]);

        $violations = $this->check->check($this->rule(14), $user, $date->copy(), $date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function shift_outside_the_lead_time_is_fine(): void
    {
        $user = User::factory()->create();
        $date = Carbon::now()->startOfDay()->addDays(20);
        $this->shiftFor($user, $date, '08:00:00', '16:00:00', ['is_committed' => false]);

        $violations = $this->check->check($this->rule(14), $user, $date->copy(), $date->copy());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function covered_range_is_limited_to_today_until_lead_time(): void
    {
        $rule = $this->rule(14);
        $today = Carbon::now()->startOfDay();

        $range = $this->check->getCoveredRange($rule, $today->copy()->subDays(10), $today->copy()->addDays(30));

        $this->assertNotNull($range);
        $this->assertSame($today->toDateString(), $range[0]->toDateString());
        $this->assertSame($today->copy()->addDays(14)->toDateString(), $range[1]->toDateString());

        // Zeitraum vollständig in der Vergangenheit: nichts abgedeckt
        $this->assertNull($this->check->getCoveredRange($rule, $today->copy()->subDays(10), $today->copy()->subDay()));
    }
}
