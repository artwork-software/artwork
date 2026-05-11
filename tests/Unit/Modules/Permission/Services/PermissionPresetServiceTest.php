<?php

namespace Tests\Unit\Modules\Permission\Services;

use Artwork\Modules\Permission\Http\Requests\StorePermissionPresetRequest;
use Artwork\Modules\Permission\Http\Requests\UpdatePermissionPresetRequest;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Permission\Models\PermissionPreset;
use Artwork\Modules\Permission\Services\PermissionPresetService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PermissionPresetServiceTest extends TestCase
{
    private PermissionPresetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PermissionPresetService::class);
    }

    #[Test]
    public function get_permission_presets_returns_collection(): void
    {
        $existingCount = PermissionPreset::count();
        PermissionPreset::factory()->count(2)->create();

        $presets = $this->service->getPermissionPresets();

        $this->assertInstanceOf(Collection::class, $presets);
        $this->assertCount($existingCount + 2, $presets);
    }

    #[Test]
    public function get_available_permissions_groups_by_permission_group(): void
    {
        Permission::create(['name' => 'p one', 'guard_name' => 'web', 'group' => 'group_a']);
        Permission::create(['name' => 'p two', 'guard_name' => 'web', 'group' => 'group_b']);
        Permission::create(['name' => 'p three', 'guard_name' => 'web', 'group' => 'group_a']);

        $grouped = $this->service->getAvailablePermissions();

        $this->assertTrue($grouped->has('group_a'));
        $this->assertTrue($grouped->has('group_b'));
        $this->assertCount(2, $grouped->get('group_a'));
        $this->assertCount(1, $grouped->get('group_b'));
    }

    #[Test]
    public function create_from_request_persists_preset(): void
    {
        $request = StorePermissionPresetRequest::create('/test', 'POST', [
            'name' => 'My Preset',
            'permissions' => ['can edit', 'can view'],
        ]);

        $this->service->createFromRequest($request);

        $this->assertDatabaseHas('permission_presets', [
            'name' => 'My Preset',
        ]);
    }

    #[Test]
    public function update_from_request_modifies_preset(): void
    {
        $preset = PermissionPreset::factory()->create(['name' => 'Old', 'permissions' => ['old']]);

        $request = UpdatePermissionPresetRequest::create('/test', 'PUT', [
            'name' => 'New',
            'permissions' => ['new1', 'new2'],
        ]);

        $this->service->updateFromRequest($request, $preset);

        $fresh = $preset->fresh();
        $this->assertSame('New', $fresh->name);
        $this->assertSame(['new1', 'new2'], $fresh->permissions);
    }

    #[Test]
    public function destroy_removes_preset(): void
    {
        $preset = PermissionPreset::factory()->create();

        $this->service->destroy($preset);

        $this->assertDatabaseMissing('permission_presets', ['id' => $preset->id]);
    }
}
