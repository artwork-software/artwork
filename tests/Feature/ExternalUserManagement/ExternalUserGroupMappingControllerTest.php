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
 * „Tool-Einstellungen ändern“, die Admin-Rolle nur durch Admins.
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
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

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
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

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
}
