<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 3a "Meine Zahlen": Die shift-info-Endpunkte sind für die eigene Person mit
 * "can view own roster" erreichbar (UserPolicy::viewShiftKpis); fremde Personen weiterhin
 * nur mit "can view shift user kpis".
 */
final class ShiftUserInfoAuthorizationTest extends FeatureTestCase
{
    #[Test]
    public function own_person_with_own_roster_permission_can_load_own_kpis(): void
    {
        $user = $this->actingAsUserWith([PermissionEnum::CAN_VIEW_OWN_ROSTER->value]);

        $this->getJson(route('shift.user-info.compensation', $user))->assertOk();
        $this->getJson(route('shift.user-info.vacation', $user))->assertOk();
        $this->getJson(route('shift.user-info.worktimes', $user))->assertOk();
        $this->getJson(route('shift.user-info.overtime', $user))->assertOk();
        $this->getJson(route('shift.user-info.violations', $user))->assertOk();
    }

    #[Test]
    public function own_roster_permission_does_not_open_foreign_kpis(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_VIEW_OWN_ROSTER->value]);
        $other = User::factory()->create();

        $this->getJson(route('shift.user-info.compensation', $other))->assertForbidden();
        $this->getJson(route('shift.user-info.violations', $other))->assertForbidden();
        $this->getJson(route('shift.user-info.worktimes', $other))->assertForbidden();
    }

    #[Test]
    public function own_person_without_own_roster_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->getJson(route('shift.user-info.compensation', $user))->assertForbidden();
        $this->getJson(route('shift.user-info.violations', $user))->assertForbidden();
    }

    #[Test]
    public function kpi_permission_still_opens_foreign_kpis(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_VIEW_SHIFT_USER_KPIS->value]);
        $other = User::factory()->create();

        $this->getJson(route('shift.user-info.compensation', $other))->assertOk();
        $this->getJson(route('shift.user-info.violations', $other))->assertOk();
    }

    #[Test]
    public function violations_endpoint_lists_only_active_violations_of_the_person(): void
    {
        $user = $this->actingAsUserWith([PermissionEnum::CAN_VIEW_OWN_ROSTER->value]);
        $other = User::factory()->create();

        $rule = ShiftRule::factory()->create([
            'name' => 'Ruhezeit 11h',
            'trigger_type' => 'restTimeBeforeWorkday',
            'individual_number_value' => 11,
        ]);

        $active = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'violation_date' => '2026-05-06',
            'status' => 'active',
            'violation_data' => ['measured' => '9:30'],
        ]);
        ShiftRuleViolation::factory()->resolved($user->id)->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $user->id,
            'violation_date' => '2026-05-07',
        ]);
        ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'user_id' => $other->id,
            'violation_date' => '2026-05-08',
            'status' => 'active',
        ]);

        $response = $this->getJson(route('shift.user-info.violations', $user))->assertOk();

        $response->assertJsonPath('count', 1);
        $response->assertJsonPath('violations.0.id', $active->id);
        $response->assertJsonPath('violations.0.violation_date', '2026-05-06');
        $response->assertJsonPath('violations.0.rule_name', 'Ruhezeit 11h');
        $response->assertJsonPath('violations.0.display_name', 'Ruhezeit 11h');
        $response->assertJsonPath('violations.0.status', 'active');
        $response->assertJsonPath('violations.0.violation_data.measured', '9:30');
    }
}
