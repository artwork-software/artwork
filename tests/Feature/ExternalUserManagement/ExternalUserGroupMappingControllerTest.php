<?php

namespace Tests\Feature\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserGroupMapping;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

/**
 * Gruppen-Mappings vergeben beim LDAP-Sync Rechte und Rollen – Verwaltung nur mit
 * „Tool-Einstellungen ändern“, die Admin-Rolle nur durch Admins, Rechte nur, wenn die
 * handelnde Person sie selbst besitzt (Admins: alle).
 */
final class ExternalUserGroupMappingControllerTest extends FeatureTestCase
{
    private ExternalUserSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = ExternalUserSource::query()->create([
            'name' => 'LDAP',
            'active' => true,
            'type' => 'ldap',
            'config' => ['host' => 'ldap.example.test'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'source_id' => $this->source->id,
            'ad_group_dn' => 'cn=technik,ou=groups,dc=example,dc=test',
            'ad_group_name' => 'Technik',
            'permission_ids' => [Permission::findOrCreate(PermissionEnum::VIEW_SHIFT_PLAN->value, 'web')->id],
            'role_ids' => [],
        ], $overrides);
    }

    #[Test]
    public function users_without_tool_settings_permission_cannot_manage_mappings(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('tool.external-user-management.group-mappings.store'), $this->payload())
            ->assertForbidden();
        $this->getJson(route('tool.external-user-management.group-mappings.index', $this->source->id))
            ->assertForbidden();
    }

    #[Test]
    public function tool_settings_managers_can_create_update_and_delete_mappings(): void
    {
        $this->actingAsUserWith([PermissionEnum::SETTINGS_UPDATE->value, PermissionEnum::VIEW_SHIFT_PLAN->value]);

        $this->postJson(route('tool.external-user-management.group-mappings.store'), $this->payload())
            ->assertCreated();
        $mapping = ExternalUserGroupMapping::query()->where('source_id', $this->source->id)->sole();

        $this->putJson(
            route('tool.external-user-management.group-mappings.update', $mapping),
            $this->payload(['ad_group_name' => 'Technik & Licht'])
        )->assertOk();
        $this->assertSame('Technik & Licht', $mapping->fresh()->ad_group_name);

        $this->getJson(route('tool.external-user-management.group-mappings.index', $this->source->id))
            ->assertOk()
            ->assertJsonCount(1);

        $this->deleteJson(route('tool.external-user-management.group-mappings.destroy', $mapping))->assertOk();
        $this->assertSoftDeleted($mapping);
    }

    #[Test]
    public function only_admins_can_map_a_directory_group_to_the_admin_role(): void
    {
        $adminRole = Role::findOrCreate(RoleEnum::ARTWORK_ADMIN->value, 'web');
        $this->actingAsUserWith([PermissionEnum::SETTINGS_UPDATE->value, PermissionEnum::VIEW_SHIFT_PLAN->value]);

        $this->postJson(
            route('tool.external-user-management.group-mappings.store'),
            $this->payload(['role_ids' => [$adminRole->id]])
        )->assertJsonValidationErrors('role_ids.0');
        $this->assertSame(0, ExternalUserGroupMapping::query()->where('source_id', $this->source->id)->count());

        $this->actingAsAdmin();
        $this->postJson(
            route('tool.external-user-management.group-mappings.store'),
            $this->payload(['role_ids' => [$adminRole->id]])
        )->assertCreated();
    }

    #[Test]
    public function only_admins_can_change_or_delete_a_mapping_that_grants_the_admin_role(): void
    {
        $adminRole = Role::findOrCreate(RoleEnum::ARTWORK_ADMIN->value, 'web');
        $mapping = ExternalUserGroupMapping::query()->create($this->payload(['role_ids' => [$adminRole->id]]));
        $this->actingAsUserWith([PermissionEnum::SETTINGS_UPDATE->value, PermissionEnum::VIEW_SHIFT_PLAN->value]);

        // vorher: Löschen entzog beim nächsten Sync allen Admins der Gruppe die Rolle
        $this->deleteJson(route('tool.external-user-management.group-mappings.destroy', $mapping))->assertForbidden();
        $this->putJson(
            route('tool.external-user-management.group-mappings.update', $mapping),
            $this->payload(['role_ids' => []])
        )->assertForbidden();
        $this->assertSame([$adminRole->id], array_map('intval', $mapping->fresh()->role_ids));

        $this->actingAsAdmin();
        $this->deleteJson(route('tool.external-user-management.group-mappings.destroy', $mapping))->assertOk();
    }

    #[Test]
    public function tool_settings_managers_can_only_map_permissions_they_hold_themselves(): void
    {
        $settingsPermission = Permission::findOrCreate(PermissionEnum::SETTINGS_UPDATE->value, 'web');
        $heldPermission = Permission::findOrCreate(PermissionEnum::VIEW_SHIFT_PLAN->value, 'web');
        $notHeldPermission = Permission::findOrCreate(PermissionEnum::PROJECT_MANAGEMENT->value, 'web');
        $this->actingAsUserWith([$settingsPermission->name, $heldPermission->name]);

        $this->postJson(
            route('tool.external-user-management.group-mappings.store'),
            $this->payload(['permission_ids' => [$heldPermission->id, $notHeldPermission->id]])
        )->assertJsonValidationErrors('permission_ids.1')
            ->assertJsonMissingValidationErrors('permission_ids.0');
        $this->assertSame(0, ExternalUserGroupMapping::query()->where('source_id', $this->source->id)->count());

        $this->postJson(
            route('tool.external-user-management.group-mappings.store'),
            $this->payload(['permission_ids' => [$heldPermission->id]])
        )->assertCreated();
        $mapping = ExternalUserGroupMapping::query()->where('source_id', $this->source->id)->sole();

        $this->putJson(
            route('tool.external-user-management.group-mappings.update', $mapping),
            $this->payload(['permission_ids' => [$heldPermission->id, $notHeldPermission->id]])
        )->assertJsonValidationErrors('permission_ids.1');
        $this->assertSame([$heldPermission->id], array_map('intval', $mapping->fresh()->permission_ids));

        $this->actingAsAdmin();
        $this->putJson(
            route('tool.external-user-management.group-mappings.update', $mapping),
            $this->payload(['permission_ids' => [$heldPermission->id, $notHeldPermission->id]])
        )->assertOk();
    }

    #[Test]
    public function tool_settings_managers_cannot_change_or_delete_mappings_with_permissions_they_do_not_hold(): void
    {
        $notHeldPermission = Permission::findOrCreate(PermissionEnum::PROJECT_MANAGEMENT->value, 'web');
        $mapping = ExternalUserGroupMapping::query()->create(
            $this->payload(['permission_ids' => [$notHeldPermission->id]])
        );
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        // Löschen/Leeren entzöge beim nächsten Sync allen Personen der Gruppe das fremde Recht.
        $this->putJson(
            route('tool.external-user-management.group-mappings.update', $mapping),
            ['permission_ids' => []]
        )->assertForbidden();
        $this->deleteJson(route('tool.external-user-management.group-mappings.destroy', $mapping))
            ->assertForbidden();
        $this->assertSame([$notHeldPermission->id], array_map('intval', $mapping->fresh()->permission_ids));
        $this->assertNotSoftDeleted($mapping);
    }
}
