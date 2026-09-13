<?php

namespace Tests\Unit\Modules\Shift\Jobs;

use Artwork\Modules\Shift\Jobs\RevalidateShiftRulesJob;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleRevalidationService;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Neuprüfung bei Regel-/Vertragsänderung: Job wird angestoßen und erzeugt/entfernt Verstöße auch
 * jenseits der 14-Tage-Sicht des Crons.
 */
final class RevalidateShiftRulesJobTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    #[Test]
    public function creating_a_rule_dispatches_the_job_for_the_contract_members(): void
    {
        [$user, $contract] = $this->userWithContract();
        Bus::fake();

        app(ShiftRuleService::class)->createRule([
            'name' => 'Tagesmaximum',
            'description' => '',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'warning_color' => '#ff0000',
            'is_active' => true,
        ], [$contract->id]);

        Bus::assertDispatched(
            RevalidateShiftRulesJob::class,
            fn (RevalidateShiftRulesJob $job): bool => in_array($user->id, $job->userIds, true)
        );
    }

    #[Test]
    public function updating_and_deleting_a_rule_dispatch_the_job(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        Bus::fake();

        app(ShiftRuleService::class)->updateRule($rule, ['individual_number_value' => 9.0], [$contract->id]);
        app(ShiftRuleService::class)->deleteRule($rule);

        Bus::assertDispatchedTimes(RevalidateShiftRulesJob::class, 2);
    }

    #[Test]
    public function changing_the_contract_assignment_of_a_person_dispatches_the_job(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $contract = UserContract::factory()->create();

        $assign = UserContractAssign::factory()->create([
            'user_id' => $user->id,
            'user_contract_id' => $contract->id,
        ]);
        $assign->update(['user_contract_id' => UserContract::factory()->create()->id]);
        $assign->delete();

        Bus::assertDispatched(
            RevalidateShiftRulesJob::class,
            fn (RevalidateShiftRulesJob $job): bool => $job->userIds === [$user->id]
        );
        Bus::assertDispatchedTimes(RevalidateShiftRulesJob::class, 3);
    }

    #[Test]
    public function deleting_a_rule_removes_its_active_automatic_violations(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        Bus::fake();
        $automatic = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id, 'shift_id' => null, 'user_id' => $user->id, 'status' => 'active', 'is_manual' => false,
        ]);
        $manual = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id, 'shift_id' => null, 'user_id' => $user->id, 'status' => 'active', 'is_manual' => true,
        ]);
        $resolved = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id, 'shift_id' => null, 'user_id' => $user->id, 'status' => 'resolved', 'is_manual' => false,
        ]);

        app(ShiftRuleService::class)->deleteRule($rule);

        $this->assertDatabaseMissing('shift_rule_violations', ['id' => $automatic->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $manual->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $resolved->id]);
    }

    #[Test]
    public function the_job_creates_and_removes_violations_beyond_the_fourteen_day_window(): void
    {
        Bus::fake();
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $date = Carbon::today()->addDays(40);
        $shift = $this->shiftFor($user, $date, '08:00:00', '18:00:00'); // 10 h

        $from = Carbon::today()->toDateString();
        $to = Carbon::today()->addDays(60)->toDateString();
        (new RevalidateShiftRulesJob([$user->id], $from, $to))->handle(app(ShiftRuleService::class));

        $this->assertDatabaseHas('shift_rule_violations', [
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'violation_date' => $date->toDateString(),
            'status' => 'active',
        ]);

        // Schicht verkürzt -> Job entfernt den Verstoß wieder
        $shift->update(['end' => '16:00:00']);
        (new RevalidateShiftRulesJob([$user->id], $from, $to))->handle(app(ShiftRuleService::class));

        $this->assertDatabaseMissing('shift_rule_violations', [
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function the_range_extends_to_the_latest_future_shift_and_is_capped_at_twelve_months(): void
    {
        Bus::fake();
        [$user] = $this->userWithContract();
        $service = app(ShiftRuleRevalidationService::class);

        [$from, $to] = $service->rangeForUsers([$user->id]);
        $this->assertSame(Carbon::today()->toDateString(), $from->toDateString());
        $this->assertSame(Carbon::today()->addDays(ShiftRuleRevalidationService::MIN_DAYS_AHEAD)->toDateString(), $to->toDateString());

        $farShiftDate = Carbon::today()->addDays(100);
        $this->shiftFor($user, $farShiftDate);
        [, $to] = $service->rangeForUsers([$user->id]);
        $this->assertSame($farShiftDate->toDateString(), $to->toDateString());

        $this->shiftFor($user, Carbon::today()->addMonths(20));
        [, $to] = $service->rangeForUsers([$user->id]);
        $this->assertSame(Carbon::today()->addMonths(ShiftRuleRevalidationService::MAX_MONTHS_AHEAD)->toDateString(), $to->toDateString());
    }
}
