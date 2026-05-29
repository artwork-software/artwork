<?php

namespace Tests\Feature\ExternalAccess\Permission;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InviteExternalPermissionTest extends TestCase
{
    #[Test]
    public function permission_is_defined_in_base_data_provider_and_seeded(): void
    {
        $definition = collect((new BaseDataProvider())->getPermissions())
            ->firstWhere('name', PermissionEnum::INVITE_EXTERNAL->value);

        $this->assertNotNull($definition, 'INVITE_EXTERNAL must be defined in BaseDataProvider.');
        $this->assertSame('CRM', $definition['group']);

        // ensureRolesAndPermissionsSeeded runs the seeder which builds from the data provider.
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);

        $this->assertTrue(
            Permission::query()->where('name', PermissionEnum::INVITE_EXTERNAL->value)->exists()
        );
    }

    #[Test]
    public function permission_is_added_idempotently_via_migration_on_existing_instance(): void
    {
        // Simulate an existing instance that never had the permission.
        Permission::query()->where('name', PermissionEnum::INVITE_EXTERNAL->value)->delete();
        $this->assertFalse(
            Permission::query()->where('name', PermissionEnum::INVITE_EXTERNAL->value)->exists()
        );

        $migration = require database_path('migrations/2026_05_29_100000_add_invite_external_permission.php');

        $migration->up();
        $this->assertSame(
            1,
            Permission::query()->where('name', PermissionEnum::INVITE_EXTERNAL->value)->count()
        );

        // Running it again must not create a duplicate.
        $migration->up();
        $this->assertSame(
            1,
            Permission::query()->where('name', PermissionEnum::INVITE_EXTERNAL->value)->count()
        );
    }

    #[Test]
    public function artwork_admin_implicitly_has_permission_via_gate_before(): void
    {
        $admin = $this->adminUser();

        $this->assertTrue($admin->can(PermissionEnum::INVITE_EXTERNAL->value));
    }

    #[Test]
    public function user_without_role_or_permission_cannot_invite(): void
    {
        // Seed permissions, then check a fresh user that has neither the role nor the permission.
        $this->actingAsUserWith([]);
        $user = User::factory()->create();

        $this->assertFalse($user->can(PermissionEnum::INVITE_EXTERNAL->value));
    }
}
