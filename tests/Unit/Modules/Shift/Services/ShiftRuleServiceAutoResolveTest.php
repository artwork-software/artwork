<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Automatische Auflösung: Nach einem Regellauf werden aktive, automatische Verstöße im geprüften
 * Zeitraum gelöscht, die der Lauf nicht (neu) erzeugt/bestätigt hat.
 */
final class ShiftRuleServiceAutoResolveTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private ShiftRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftRuleService::class);
    }

    #[Test]
    public function violation_is_removed_when_the_shift_no_longer_violates_the_rule(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $shift = $this->shiftFor($user, $date, '08:00:00', '18:00:00'); // 10 h

        $this->service->validateRulesForUser($user, $date->copy(), $date->copy());
        $this->assertDatabaseHas('shift_rule_violations', [
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        // Schicht verkürzt: 8 h -> kein Verstoß mehr
        $shift->update(['end' => '16:00:00']);
        $this->service->validateRulesForUser($user, $date->copy(), $date->copy());

        $this->assertDatabaseMissing('shift_rule_violations', [
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function violation_is_removed_when_the_person_is_removed_from_the_shift(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $shift = $this->shiftFor($user, $date, '08:00:00', '18:00:00');

        $this->service->validateRulesForUser($user, $date->copy(), $date->copy());
        $this->assertSame(1, ShiftRuleViolation::where('user_id', $user->id)->count());

        $shift->users()->detach($user->id);
        $this->service->validateRulesForUser($user, $date->copy(), $date->copy());

        $this->assertSame(0, ShiftRuleViolation::where('user_id', $user->id)->count());
    }

    #[Test]
    public function resolved_ignored_manual_and_child_violations_are_kept(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $shift = $this->shiftFor($user, $date, '08:00:00', '18:00:00');

        $active = $this->service->validateRulesForUser($user, $date->copy(), $date->copy())->first();

        $resolved = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'violation_date' => $date->copy()->addDay()->toDateString(),
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
        $ignored = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'violation_date' => $date->copy()->addDays(2)->toDateString(),
            'status' => 'ignored',
            'resolved_at' => now(),
        ]);
        $manual = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => null,
            'violation_date' => $date->copy()->addDays(3)->toDateString(),
            'status' => 'active',
            'is_manual' => true,
        ]);
        $child = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => null,
            'violation_date' => $date->copy()->addDays(4)->toDateString(),
            'status' => 'active',
            'severity' => 'error',
            'is_manual' => false,
            'parent_violation_id' => $active->id,
        ]);

        // Auslöser entfernen und den ganzen Zeitraum neu prüfen
        $shift->users()->detach($user->id);
        $this->service->validateRulesForUser($user, $date->copy(), $date->copy()->addDays(6));

        $this->assertDatabaseMissing('shift_rule_violations', ['id' => $active->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $resolved->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $ignored->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $manual->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $child->id]);
    }

    #[Test]
    public function violations_with_compensation_day_offs_are_kept(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $shift = $this->shiftFor($user, $date, '08:00:00', '18:00:00');

        $violation = $this->service->validateRulesForUser($user, $date->copy(), $date->copy())->first();
        CompensationDayOff::create([
            'user_id' => $user->id,
            'violation_id' => $violation->id,
            'value' => 1.0,
            'deadline' => $date->copy()->addDays(30)->toDateString(),
        ]);

        $shift->users()->detach($user->id);
        $this->service->validateRulesForUser($user, $date->copy(), $date->copy());

        $this->assertDatabaseHas('shift_rule_violations', ['id' => $violation->id]);
    }

    #[Test]
    public function violations_outside_the_checked_range_are_kept(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $shift = $this->shiftFor($user, $date, '08:00:00', '18:00:00');

        $violation = $this->service->validateRulesForUser($user, $date->copy(), $date->copy())->first();

        // Nur den Folgetag prüfen: der Verstoß von $date liegt außerhalb und bleibt
        $shift->users()->detach($user->id);
        $this->service->validateRulesForUser($user, $date->copy()->addDay(), $date->copy()->addDay());

        $this->assertDatabaseHas('shift_rule_violations', ['id' => $violation->id]);
    }

    #[Test]
    public function without_contract_all_automatic_active_violations_in_range_are_removed(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $date, '08:00:00', '18:00:00');

        $violation = $this->service->validateRulesForUser($user, $date->copy(), $date->copy())->first();
        $manual = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => null,
            'violation_date' => $date->toDateString(),
            'status' => 'active',
            'is_manual' => true,
        ]);

        UserContractAssign::where('user_id', $user->id)->delete();
        $result = $this->service->validateRulesForUser($user->fresh(), $date->copy(), $date->copy());

        $this->assertCount(0, $result);
        $this->assertDatabaseMissing('shift_rule_violations', ['id' => $violation->id]);
        $this->assertDatabaseHas('shift_rule_violations', ['id' => $manual->id]);
    }

    #[Test]
    public function violations_of_rules_no_longer_assigned_are_removed(): void
    {
        [$user, $contract] = $this->userWithContract();
        $rule = $this->ruleForContract($contract, 'maxWorkingHoursOnDay', 8.0);
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $this->shiftFor($user, $date, '08:00:00', '18:00:00');

        $violation = $this->service->validateRulesForUser($user, $date->copy(), $date->copy())->first();

        // Regel vom Vertrag entfernen, Schicht bleibt unverändert
        $rule->contracts()->sync([]);
        $this->service->validateRulesForUser($user, $date->copy(), $date->copy());

        $this->assertDatabaseMissing('shift_rule_violations', ['id' => $violation->id]);
    }

    #[Test]
    public function date_range_run_also_cleans_up_people_without_contract(): void
    {
        $user = User::factory()->create();
        $rule = \Artwork\Modules\Shift\Models\ShiftRule::factory()->create([
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
        ]);
        $date = $this->futureWeekday(Carbon::TUESDAY);
        $stale = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'shift_id' => null,
            'violation_date' => $date->toDateString(),
            'status' => 'active',
            'is_manual' => false,
        ]);

        $this->service->validateShiftRulesForDateRange($date->copy(), $date->copy());

        $this->assertDatabaseMissing('shift_rule_violations', ['id' => $stale->id]);
    }
}
