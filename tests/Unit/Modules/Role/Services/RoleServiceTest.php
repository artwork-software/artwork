<?php

namespace Tests\Unit\Modules\Role\Services;

use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Role\Models\Role;
use Artwork\Modules\Role\Services\RoleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoleServiceTest extends TestCase
{
    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RoleService::class);
    }

    #[Test]
    public function save_persists_role(): void
    {
        $role = new Role(['name' => 'editor', 'guard_name' => 'web']);

        $result = $this->service->save($role);

        $this->assertTrue($result->exists);
        $this->assertDatabaseHas('roles', ['name' => 'editor']);
    }

    #[Test]
    public function create_from_array_persists_role(): void
    {
        $role = $this->service->createFromArray(['name' => 'reviewer', 'guard_name' => 'web']);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertDatabaseHas('roles', ['name' => 'reviewer']);
    }

    #[Test]
    public function find_by_name_returns_role_when_exists(): void
    {
        Role::create(['name' => 'maintainer', 'guard_name' => 'web']);

        $role = $this->service->findByName('maintainer');

        $this->assertNotNull($role);
        $this->assertSame('maintainer', $role->name);
    }

    #[Test]
    public function find_by_name_returns_null_when_missing(): void
    {
        $role = $this->service->findByName('nonexistent');

        $this->assertNull($role);
    }

    #[Test]
    public function get_table_fields_returns_columns(): void
    {
        $fields = $this->service->getTableFields();

        $this->assertNotEmpty($fields);
        $names = collect($fields)->pluck('Field');
        $this->assertTrue($names->contains('name'));
        $this->assertTrue($names->contains('guard_name'));
    }

    #[Test]
    public function get_all_returns_all_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $roles = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $roles);
        $this->assertGreaterThanOrEqual(1, $roles->count());
        $this->assertTrue(
            $roles->contains('name', RoleEnum::ARTWORK_ADMIN->value)
        );
    }

    #[Test]
    public function get_all_role_names_returns_string_collection(): void
    {
        Role::create(['name' => 'r1', 'guard_name' => 'web']);
        Role::create(['name' => 'r2', 'guard_name' => 'web']);

        $names = $this->service->getAllRoleNames();

        $this->assertInstanceOf(SupportCollection::class, $names);
        $this->assertTrue($names->contains('r1'));
        $this->assertTrue($names->contains('r2'));
    }
}
