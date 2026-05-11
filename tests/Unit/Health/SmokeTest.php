<?php

namespace Tests\Unit\Health;

use Artwork\Modules\Role\Enums\RoleEnum;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class SmokeTest extends TestCase
{
    #[Test]
    public function arithmetic_still_works(): void
    {
        $this->assertSame(2, 1 + 1);
    }

    #[Test]
    public function database_connection_is_test_database(): void
    {
        $this->assertSame('artwork_test', config('database.connections.mysql.database'));
    }

    #[Test]
    public function users_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
    }

    #[Test]
    public function roles_can_be_seeded(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertGreaterThan(0, Role::query()->count());
        $this->assertTrue(
            Role::query()->where('name', RoleEnum::ARTWORK_ADMIN->value)->exists()
        );
    }

    #[Test]
    public function admin_user_helper_works(): void
    {
        $admin = $this->adminUser();

        $this->assertTrue($admin->hasRole(RoleEnum::ARTWORK_ADMIN->value));
    }

    #[Test]
    public function acting_as_admin_authenticates_user(): void
    {
        $admin = $this->actingAsAdmin();

        $this->assertAuthenticatedAs($admin);
    }
}
