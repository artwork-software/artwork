<?php

namespace Tests\Unit\Modules\Permission\Services;

use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Permission\Services\PermissionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PermissionServiceTest extends TestCase
{
    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PermissionService::class);
    }

    #[Test]
    public function save_persists_permission(): void
    {
        $permission = new Permission(['name' => 'can view things', 'guard_name' => 'web']);

        $result = $this->service->save($permission);

        $this->assertTrue($result->exists);
        $this->assertDatabaseHas('permissions', ['name' => 'can view things']);
    }

    #[Test]
    public function create_from_array_persists_permission(): void
    {
        $permission = $this->service->createFromArray([
            'name' => 'can edit things',
            'guard_name' => 'web',
        ]);

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertDatabaseHas('permissions', ['name' => 'can edit things']);
    }

    #[Test]
    public function find_by_name_returns_permission_when_exists(): void
    {
        Permission::create(['name' => 'can manage things', 'guard_name' => 'web']);

        $permission = $this->service->findByName('can manage things');

        $this->assertNotNull($permission);
        $this->assertSame('can manage things', $permission->name);
    }

    #[Test]
    public function find_by_name_returns_null_when_missing(): void
    {
        $permission = $this->service->findByName('non existing permission');

        $this->assertNull($permission);
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
    public function get_all_returns_collection_of_permissions(): void
    {
        Permission::create(['name' => 'p1', 'guard_name' => 'web']);
        Permission::create(['name' => 'p2', 'guard_name' => 'web']);

        $all = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $all);
        $this->assertGreaterThanOrEqual(2, $all->count());
    }

    #[Test]
    public function get_all_permission_names_returns_string_collection(): void
    {
        Permission::create(['name' => 'unique p1', 'guard_name' => 'web']);
        Permission::create(['name' => 'unique p2', 'guard_name' => 'web']);

        $names = $this->service->getAllPermissionNames();

        $this->assertInstanceOf(SupportCollection::class, $names);
        $this->assertTrue($names->contains('unique p1'));
        $this->assertTrue($names->contains('unique p2'));
    }
}
