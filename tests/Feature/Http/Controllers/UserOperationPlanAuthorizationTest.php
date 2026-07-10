<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

final class UserOperationPlanAuthorizationTest extends FeatureTestCase
{
    private function givePermission(User $user, PermissionEnum $permission): void
    {
        Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
        $user->givePermissionTo($permission->value);
    }

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $user = User::factory()->create();

        $this->get(route('user.operationPlan', $user))->assertRedirect(route('login'));
    }

    #[Test]
    public function user_without_own_roster_permission_cannot_view_own_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $user))->assertForbidden();
    }

    #[Test]
    public function user_with_own_roster_permission_can_view_own_plan(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, PermissionEnum::CAN_VIEW_OWN_ROSTER);
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $user))->assertOk();
    }

    #[Test]
    public function operation_plan_contains_visible_shift_rule_violations_for_each_day(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->givePermission($user, PermissionEnum::CAN_VIEW_OWN_ROSTER);
        $this->actingAs($user);

        $startDate = now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
        $violationDate = $startDate->copy()->addDay();

        $user->workerShiftPlanFilter()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $shiftRule = ShiftRule::factory()->create([
            'name' => 'Maximum working hours',
            'description' => 'Daily working hours exceeded',
            'warning_color' => '#f97316',
        ]);

        $visibleViolation = ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $shiftRule->id,
            'user_id' => $user->id,
            'violation_date' => $violationDate,
            'status' => 'active',
        ]);

        ShiftRuleViolation::factory()->ignored($user->id)->create([
            'shift_rule_id' => $shiftRule->id,
            'user_id' => $user->id,
            'violation_date' => $violationDate,
        ]);

        ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $shiftRule->id,
            'user_id' => $otherUser->id,
            'violation_date' => $violationDate,
        ]);

        $response = $this->get(route('user.operationPlan', $user));

        $response->assertOk();

        $violations = $response->inertiaProps(
            'daysWithData.' . $violationDate->format('Y-m-d') . '.violations'
        );

        $this->assertCount(1, $violations);
        $this->assertSame($visibleViolation->id, $violations[0]['id']);
        $this->assertSame('active', $violations[0]['status']);
        $this->assertSame('Maximum working hours', $violations[0]['shift_rule']['name']);
        $this->assertSame('Daily working hours exceeded', $violations[0]['shift_rule']['description']);
        $this->assertSame('#f97316', $violations[0]['shift_rule']['warning_color']);
    }

    #[Test]
    public function own_roster_permission_does_not_grant_access_to_foreign_plans(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::CAN_VIEW_OWN_ROSTER);
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $other))->assertForbidden();
    }

    #[Test]
    public function team_manager_can_view_foreign_plans(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::TEAM_UPDATE);
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $other))->assertOk();
    }

    #[Test]
    public function worker_manager_can_view_foreign_plans(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::MA_MANAGER);
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $other))->assertOk();
    }

    #[Test]
    public function admin_can_view_foreign_plans(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Role::query()->firstOrCreate(['name' => RoleEnum::ARTWORK_ADMIN->value, 'guard_name' => 'web']);
        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $other))->assertOk();
    }
}
