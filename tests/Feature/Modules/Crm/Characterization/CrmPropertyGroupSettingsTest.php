<?php

namespace Tests\Feature\Modules\Crm\Characterization;

use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Eigenschaftsgruppen-Endpunkte (anlegen, ändern,
 * sortieren, löschen, Gruppenrechte setzen) fest. Gruppenrechte steuern die Sicht-/
 * Bearbeitbarkeit vertraulicher Gruppen; die Routen selbst sind nur für `crm manager`.
 */
final class CrmPropertyGroupSettingsTest extends FeatureTestCase
{
    private function userMorph(): string
    {
        return (new User())->getMorphClass();
    }

    private function departmentMorph(): string
    {
        return (new Department())->getMorphClass();
    }

    #[Test]
    public function store_creates_group_and_its_permissions(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $viewer = User::factory()->create();

        $this->post(route('crm.groups.store'), [
            'name' => 'Vertraulich',
            'icon' => 'lock',
            'is_confidential' => true,
            'permissions' => [
                [
                    'permissionable_type' => $this->userMorph(),
                    'permissionable_id' => $viewer->id,
                    'can_view' => true,
                ],
            ],
        ])->assertRedirect();

        $group = CrmPropertyGroup::query()->where('name', 'Vertraulich')->firstOrFail();
        $this->assertTrue($group->is_confidential);
        $this->assertFalse((bool) $group->is_system);
        $this->assertDatabaseHas('crm_property_group_permissions', [
            'crm_property_group_id' => $group->id,
            'permissionable_type' => $this->userMorph(),
            'permissionable_id' => $viewer->id,
            'can_view' => true,
            'can_edit' => false,
        ]);
    }

    #[Test]
    public function store_validates_name_and_permission_entries(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);

        $this->post(route('crm.groups.store'), [
            'permissions' => [['can_view' => true]],
        ])->assertSessionHasErrors([
            'name',
            'permissions.0.permissionable_type',
            'permissions.0.permissionable_id',
        ]);

        $this->assertDatabaseCount('crm_property_groups', 0);
    }

    #[Test]
    public function store_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->post(route('crm.groups.store'), ['name' => 'Nope'])->assertForbidden();

        $this->assertDatabaseCount('crm_property_groups', 0);
    }

    #[Test]
    public function update_to_non_confidential_drops_all_group_permissions(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create(['is_confidential' => true]);
        $group->permissions()->create([
            'permissionable_type' => $this->userMorph(),
            'permissionable_id' => User::factory()->create()->id,
            'can_view' => true,
            'can_edit' => true,
        ]);

        $this->patch(route('crm.groups.update', $group), [
            'name' => 'Offen',
            'is_confidential' => false,
            'sort_order' => 5,
        ])->assertRedirect();

        $group->refresh();
        $this->assertSame('Offen', $group->name);
        $this->assertFalse($group->is_confidential);
        $this->assertSame(5, (int) $group->sort_order);
        $this->assertDatabaseMissing('crm_property_group_permissions', ['crm_property_group_id' => $group->id]);
    }

    #[Test]
    public function update_to_confidential_replaces_permissions_with_given_list(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create(['is_confidential' => true]);
        $oldUser = User::factory()->create();
        $department = Department::factory()->create();
        $group->permissions()->create([
            'permissionable_type' => $this->userMorph(),
            'permissionable_id' => $oldUser->id,
            'can_view' => true,
        ]);

        $this->patch(route('crm.groups.update', $group), [
            'is_confidential' => true,
            'permissions' => [
                [
                    'permissionable_type' => $this->departmentMorph(),
                    'permissionable_id' => $department->id,
                    'can_view' => true,
                    'can_edit' => true,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseMissing('crm_property_group_permissions', [
            'crm_property_group_id' => $group->id,
            'permissionable_id' => $oldUser->id,
            'permissionable_type' => $this->userMorph(),
        ]);
        $this->assertDatabaseHas('crm_property_group_permissions', [
            'crm_property_group_id' => $group->id,
            'permissionable_type' => $this->departmentMorph(),
            'permissionable_id' => $department->id,
            'can_view' => true,
            'can_edit' => true,
        ]);
    }

    #[Test]
    public function update_without_is_confidential_leaves_permissions_untouched(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create(['is_confidential' => true]);
        $group->permissions()->create([
            'permissionable_type' => $this->userMorph(),
            'permissionable_id' => User::factory()->create()->id,
            'can_view' => true,
        ]);

        $this->patch(route('crm.groups.update', $group), ['name' => 'Nur Name'])->assertRedirect();

        $this->assertSame(1, $group->permissions()->count());
    }

    #[Test]
    public function update_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $group = CrmPropertyGroup::factory()->create(['name' => 'Bleibt']);

        $this->patch(route('crm.groups.update', $group), ['name' => 'Geändert'])->assertForbidden();

        $this->assertSame('Bleibt', $group->fresh()->name);
    }

    #[Test]
    public function reorder_updates_sort_order_and_validates_ids(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $first = CrmPropertyGroup::factory()->create(['sort_order' => 0]);
        $second = CrmPropertyGroup::factory()->create(['sort_order' => 1]);

        $this->patch(route('crm.groups.reorder'), [
            'groups' => [
                ['id' => $first->id, 'sort_order' => 1],
                ['id' => $second->id, 'sort_order' => 0],
            ],
        ])->assertRedirect();

        $this->assertSame(1, (int) $first->fresh()->sort_order);
        $this->assertSame(0, (int) $second->fresh()->sort_order);

        $this->patch(route('crm.groups.reorder'), ['groups' => [['id' => 999999, 'sort_order' => 0]]])
            ->assertSessionHasErrors('groups.0.id');
    }

    #[Test]
    public function reorder_is_forbidden_for_crm_viewer(): void
    {
        $group = CrmPropertyGroup::factory()->create(['sort_order' => 3]);
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->patch(route('crm.groups.reorder'), ['groups' => [['id' => $group->id, 'sort_order' => 0]]])
            ->assertForbidden();

        $this->assertSame(3, (int) $group->fresh()->sort_order);
    }

    #[Test]
    public function destroy_deletes_custom_group_and_cascades_its_properties(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create();
        $property = CrmProperty::factory()->create(['crm_property_group_id' => $group->id]);

        $this->delete(route('crm.groups.destroy', $group))->assertRedirect();

        $this->assertDatabaseMissing('crm_property_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('crm_properties', ['id' => $property->id]);
    }

    #[Test]
    public function destroy_refuses_system_groups(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create(['is_system' => true]);

        $this->deleteJson(route('crm.groups.destroy', $group))
            ->assertUnprocessable()
            ->assertJson(['message' => 'System property groups cannot be deleted.']);

        $this->assertDatabaseHas('crm_property_groups', ['id' => $group->id]);
    }

    #[Test]
    public function destroy_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $group = CrmPropertyGroup::factory()->create();

        $this->delete(route('crm.groups.destroy', $group))->assertForbidden();

        $this->assertDatabaseHas('crm_property_groups', ['id' => $group->id]);
    }

    #[Test]
    public function update_permissions_replaces_all_entries(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create(['is_confidential' => true]);
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();
        $group->permissions()->create([
            'permissionable_type' => $this->userMorph(),
            'permissionable_id' => $oldUser->id,
            'can_view' => true,
        ]);

        $this->patch(route('crm.groups.permissions', $group), [
            'permissions' => [
                [
                    'permissionable_type' => $this->userMorph(),
                    'permissionable_id' => $newUser->id,
                    'can_view' => true,
                    'can_edit' => true,
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(1, $group->permissions()->count());
        $this->assertDatabaseHas('crm_property_group_permissions', [
            'crm_property_group_id' => $group->id,
            'permissionable_id' => $newUser->id,
            'can_edit' => true,
        ]);
    }

    #[Test]
    public function update_permissions_without_payload_removes_all_entries(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create(['is_confidential' => true]);
        $group->permissions()->create([
            'permissionable_type' => $this->userMorph(),
            'permissionable_id' => User::factory()->create()->id,
            'can_view' => true,
        ]);

        $this->patch(route('crm.groups.permissions', $group), [])->assertRedirect();

        $this->assertSame(0, $group->permissions()->count());
    }

    #[Test]
    public function update_permissions_is_forbidden_for_crm_viewer(): void
    {
        $viewer = $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $group = CrmPropertyGroup::factory()->create(['is_confidential' => true]);

        $this->patch(route('crm.groups.permissions', $group), [
            'permissions' => [
                [
                    'permissionable_type' => $this->userMorph(),
                    'permissionable_id' => $viewer->id,
                    'can_view' => true,
                    'can_edit' => true,
                ],
            ],
        ])->assertForbidden();

        $this->assertSame(0, $group->permissions()->count());
    }

    #[Test]
    public function a_group_holding_system_properties_cannot_be_deleted(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create(['is_system' => false]);
        $systemProperty = CrmProperty::factory()->create(['crm_property_group_id' => $group->id, 'is_system' => true]);

        $this->deleteJson(route('crm.groups.destroy', $group))->assertUnprocessable();

        $this->assertDatabaseHas('crm_properties', ['id' => $systemProperty->id]);
    }

    #[Test]
    public function group_permissions_only_accept_users_and_departments(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $group = CrmPropertyGroup::factory()->create();

        $this->patchJson(route('crm.groups.permissions', $group), [
            'permissions' => [[
                'permissionable_type' => CrmProperty::class,
                'permissionable_id' => 1,
                'can_view' => true,
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('permissions.0.permissionable_type');
    }
}
