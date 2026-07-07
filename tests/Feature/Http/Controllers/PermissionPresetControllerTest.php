<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Models\PermissionPreset;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class PermissionPresetControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_permission_presets_index(): void
    {
        $this->get(route('permission-presets.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_permission_presets_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('permission-presets.index'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_store_permission_preset(): void
    {
        $this->post(route('permission-presets.store'), ['name' => 'Foo'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_permission_preset(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('permission-presets.store'), [
            'name' => 'PresetA',
            'permissions' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('permission_presets', ['name' => 'PresetA']);
    }

    #[Test]
    public function admin_can_destroy_permission_preset(): void
    {
        $this->actingAsAdmin();
        $preset = PermissionPreset::create(['name' => 'X', 'permissions' => []]);

        $response = $this->delete(route('permission-presets.destroy', $preset));

        $response->assertRedirect();
        $this->assertDatabaseMissing('permission_presets', ['id' => $preset->id]);
    }
}
