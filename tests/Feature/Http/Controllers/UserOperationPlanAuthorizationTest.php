<?php

namespace Tests\Feature\Http\Controllers;

use App\Settings\ShiftSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
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
    public function user_without_any_permission_can_view_own_plan(): void
    {
        // Eigener Einsatzplan ist immer einsehbar; "can view own roster" gated nur
        // noch den Menüpunkt "Mein Einsatzplan" im Frontend.
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $user))->assertOk();
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
    public function own_plan_hides_uncommitted_shifts_when_the_instance_setting_is_enabled(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, PermissionEnum::CAN_VIEW_OWN_ROSTER);
        $this->actingAs($user);

        $shiftAttributes = [
            'event_id' => null,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start' => '10:00',
            'end' => '12:00',
            'break_minutes' => 0,
        ];
        $committedShift = Shift::factory()->create($shiftAttributes + ['is_committed' => true]);
        $uncommittedShift = Shift::factory()->create($shiftAttributes + ['is_committed' => false]);
        $qualification = ShiftQualification::factory()->create();
        $user->shifts()->attach([
            $committedShift->id => ['shift_qualification_id' => $qualification->id],
            $uncommittedShift->id => ['shift_qualification_id' => $qualification->id],
        ]);

        $settings = app(ShiftSettings::class);
        $settings->hide_uncommitted_shifts_from_own_roster = true;
        $settings->save();

        $response = $this->get(route('user.operationPlan', $user));

        $response->assertOk();
        $this->assertSame([$committedShift->id], collect($response->inertiaProps('shifts'))->pluck('id')->all());

        // daysWithData ist die tatsächliche Datenquelle des Renderings — auch dort
        // darf die nicht festgeschriebene Schicht nicht auftauchen.
        $daysWithDataShiftIds = collect($response->inertiaProps('daysWithData'))
            ->flatMap(static fn(array $day) => collect($day['shifts'])->pluck('id'))
            ->all();
        $this->assertContains($committedShift->id, $daysWithDataShiftIds);
        $this->assertNotContains($uncommittedShift->id, $daysWithDataShiftIds);
    }

    #[Test]
    public function personal_exception_keeps_uncommitted_shifts_visible_in_the_own_plan(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, PermissionEnum::CAN_VIEW_OWN_ROSTER);
        $this->givePermission($user, PermissionEnum::CAN_VIEW_OWN_UNCOMMITTED_SHIFTS);
        $this->actingAs($user);

        $committedShift = Shift::factory()->create(['is_committed' => true]);
        $uncommittedShift = Shift::factory()->create(['is_committed' => false]);
        $qualification = ShiftQualification::factory()->create();
        $user->shifts()->attach([
            $committedShift->id => ['shift_qualification_id' => $qualification->id],
            $uncommittedShift->id => ['shift_qualification_id' => $qualification->id],
        ]);

        $settings = app(ShiftSettings::class);
        $settings->hide_uncommitted_shifts_from_own_roster = true;
        $settings->save();

        $response = $this->get(route('user.operationPlan', $user));

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            [$committedShift->id, $uncommittedShift->id],
            collect($response->inertiaProps('shifts'))->pluck('id')->all()
        );
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
    public function team_management_permission_does_not_grant_access_to_foreign_plans(): void
    {
        // Teammanagement sieht Schichten nicht über andere Rechte — Zugriff auf
        // fremde Einsatzpläne bewusst entzogen (Kundenregel: nur Dienstplan-Sichtrechte).
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::TEAM_UPDATE);
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $other))->assertForbidden();
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
    public function shift_planner_can_view_foreign_plans(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::SHIFT_PLANNER);
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $other))->assertOk();
    }

    #[Test]
    public function shift_plan_viewer_can_view_foreign_plans(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::VIEW_SHIFT_PLAN);
        $this->actingAs($user);

        $this->get(route('user.operationPlan', $other))->assertOk();
    }

    #[Test]
    public function profile_shift_plan_tab_is_open_for_own_user_without_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('user.edit.shiftplan', $user))->assertOk();
    }

    #[Test]
    public function profile_shift_plan_tab_of_foreign_user_requires_roster_permissions(): void
    {
        // Kundenmeldung: der Einsatzplan-Tab war über die Nutzer*innenliste für
        // JEDE eingeloggte Person offen (Route hatte keinerlei Autorisierung).
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::CAN_VIEW_OWN_ROSTER);
        $this->actingAs($user);

        $this->get(route('user.edit.shiftplan', $other))->assertForbidden();
    }

    #[Test]
    public function profile_shift_plan_tab_of_foreign_user_opens_with_shift_plan_view_permission(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::VIEW_SHIFT_PLAN);
        $this->actingAs($user);

        $this->get(route('user.edit.shiftplan', $other))->assertOk();
    }

    #[Test]
    public function freelancer_profile_requires_roster_permissions(): void
    {
        // Freelancer-/Dienstleister-Profile enthalten den Einsatzplan und haben
        // kein "eigen" — Zugriff nur mit Dienstplan-Sichtrechten.
        $user = User::factory()->create();
        $freelancer = \Artwork\Modules\Freelancer\Models\Freelancer::factory()->create();
        $this->actingAs($user);

        $this->get(route('freelancer.show', $freelancer))->assertForbidden();

        $this->givePermission($user, PermissionEnum::VIEW_SHIFT_PLAN);
        $this->get(route('freelancer.show', $freelancer))->assertOk();
    }

    #[Test]
    public function service_provider_profile_requires_roster_permissions(): void
    {
        $user = User::factory()->create();
        $serviceProvider = \Artwork\Modules\ServiceProvider\Models\ServiceProvider::factory()->create();
        $this->actingAs($user);

        $this->get(route('service_provider.show', $serviceProvider))->assertForbidden();

        $this->givePermission($user, PermissionEnum::SHIFT_PLANNER);
        $this->get(route('service_provider.show', $serviceProvider))->assertOk();
    }

    #[Test]
    public function private_user_info_permission_opens_external_worker_profiles(): void
    {
        // Nutzer*innenverwaltungs-Verständnis: Freelancer/Dienstleister sind "User" —
        // "can view private user info" öffnet deren Profilseiten ebenfalls.
        $user = User::factory()->create();
        $freelancer = \Artwork\Modules\Freelancer\Models\Freelancer::factory()->create();
        $serviceProvider = \Artwork\Modules\ServiceProvider\Models\ServiceProvider::factory()->create();
        $this->givePermission($user, PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO);
        $this->actingAs($user);

        $this->get(route('freelancer.show', $freelancer))->assertOk();
        $this->get(route('service_provider.show', $serviceProvider))->assertOk();
    }

    #[Test]
    public function private_user_info_permission_does_not_open_foreign_plans_or_exports(): void
    {
        // Die reinen Einsatzplan-Endpunkte bleiben Roster-Rechten vorbehalten.
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO);
        $this->actingAs($user);

        $this->get(route('user.edit.shiftplan', $other))->assertForbidden();
        $this->get(route('user.operationPlan', $other))->assertForbidden();
        $this->post(route('user.shiftplan.export.monthly-pdf', $other))->assertForbidden();
    }

    #[Test]
    public function monthly_pdf_export_of_foreign_plan_requires_roster_permissions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('user.shiftplan.export.monthly-pdf', $other))->assertForbidden();

        // type/model_id-Parameter dürfen die Regel nicht umgehen (Freelancer-Export).
        $freelancer = \Artwork\Modules\Freelancer\Models\Freelancer::factory()->create();
        $this->post(route('user.shiftplan.export.monthly-pdf', $other), [
            'type' => 'freelancer',
            'model_id' => $freelancer->id,
        ])->assertForbidden();
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
