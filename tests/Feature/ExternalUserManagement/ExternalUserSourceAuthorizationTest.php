<?php

namespace Tests\Feature\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

/**
 * Verzeichnisquellen verknüpfen Logins per E-Mail mit bestehenden Konten und vergeben eine
 * Default-Rolle. Anlegen/Ändern/Löschen daher nur für artwork-Admins – „Tool-Einstellungen
 * ändern“ allein erlaubte vorher die Übernahme beliebiger Konten über einen eigenen LDAP-Host/IdP.
 */
final class ExternalUserSourceAuthorizationTest extends FeatureTestCase
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function ldapPayload(array $config = []): array
    {
        return [
            'name' => 'Directory',
            'active' => true,
            'type' => 'ldap',
            'config' => array_merge([
                'host' => 'ldap.example.test',
                'port' => 389,
                'base_dn' => 'dc=example,dc=test',
                'bind_dn' => 'cn=reader,dc=example,dc=test',
                'bind_password' => 'secret',
            ], $config),
        ];
    }

    private function source(): ExternalUserSource
    {
        return ExternalUserSource::query()->create([
            'name' => 'LDAP',
            'active' => true,
            'type' => 'ldap',
            'config' => ['host' => 'ldap.example.test', 'base_dn' => 'dc=example,dc=test'],
        ]);
    }

    #[Test]
    public function tool_settings_managers_cannot_create_change_or_delete_sources(): void
    {
        $source = $this->source();
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->postJson(route('tool.external-user-management.sources.store'), $this->ldapPayload())
            ->assertForbidden();
        $this->putJson(
            route('tool.external-user-management.sources.update', $source),
            ['name' => 'Fremdes LDAP', 'config' => ['host' => 'ldap.attacker.test']]
        )->assertForbidden();
        $this->putJson(route('tool.external-user-management.sources.update', $source), ['active' => false])
            ->assertForbidden();
        $this->deleteJson(route('tool.external-user-management.sources.destroy', $source))
            ->assertForbidden();
        $this->postJson(
            route('tool.external-user-management.sources.test-connection-config'),
            ['type' => 'ldap', 'config' => ['host' => 'ldap.attacker.test']]
        )->assertForbidden();

        $this->assertSame(1, ExternalUserSource::query()->count());
        $fresh = $source->fresh();
        $this->assertSame('LDAP', $fresh->name);
        $this->assertTrue($fresh->active);
        $this->assertSame('ldap.example.test', $fresh->config['host']);
    }

    #[Test]
    public function tool_settings_managers_cannot_make_the_admin_role_the_default_role(): void
    {
        $adminRole = Role::findOrCreate(RoleEnum::ARTWORK_ADMIN->value, 'web');
        $source = $this->source();
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->postJson(
            route('tool.external-user-management.sources.store'),
            $this->ldapPayload(['default_role_id' => $adminRole->id])
        )->assertForbidden();
        $this->putJson(
            route('tool.external-user-management.sources.update', $source),
            ['config' => ['host' => 'ldap.example.test', 'default_role_id' => $adminRole->id]]
        )->assertForbidden();

        $this->assertNull($source->fresh()->defaultRoleId());
    }

    #[Test]
    public function tool_settings_managers_still_see_the_page_test_and_sync_but_without_management(): void
    {
        $source = $this->source();
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->get(route('tool.external-user-management'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('ExternalUserManagement/Index')
                ->where('canManageSources', false));
        $this->getJson(route('tool.external-user-management.sources.index'))->assertOk();
        $this->getJson(route('tool.external-user-management.sources.sync-status', $source))->assertOk();
    }

    #[Test]
    public function admins_can_create_change_and_delete_sources(): void
    {
        $adminRole = Role::findOrCreate(RoleEnum::ARTWORK_ADMIN->value, 'web');
        $this->actingAsAdmin();

        $this->get(route('tool.external-user-management'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canManageSources', true));

        $this->postJson(route('tool.external-user-management.sources.store'), $this->ldapPayload())
            ->assertCreated();
        $source = ExternalUserSource::query()->where('name', 'Directory')->sole();

        $this->putJson(
            route('tool.external-user-management.sources.update', $source),
            ['name' => 'Directory (neu)', 'active' => false]
        )->assertOk();
        $this->assertSame('Directory (neu)', $source->fresh()->name);
        $this->assertFalse($source->fresh()->active);

        // Admins dürfen die Admin-Rolle bewusst als Default-Rolle setzen.
        $this->putJson(
            route('tool.external-user-management.sources.update', $source),
            ['config' => $this->ldapPayload(['default_role_id' => $adminRole->id])['config']]
        )->assertOk();
        $this->assertSame($adminRole->id, $source->fresh()->defaultRoleId());

        $this->deleteJson(route('tool.external-user-management.sources.destroy', $source))->assertOk();
        $this->assertSoftDeleted($source);
    }
}
