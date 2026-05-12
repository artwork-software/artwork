<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class AreaControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_area_management(): void
    {
        $this->get(route('areas.management'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_area_management(): void
    {
        $this->actingAsAdmin();

        $this->get(route('areas.management'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store_area(): void
    {
        $this->post(route('areas.store'), ['name' => 'A'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_area(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('areas.store'), ['name' => 'StageArea']);

        $response->assertRedirect();
        $this->assertDatabaseHas('areas', ['name' => 'StageArea']);
    }

    #[Test]
    public function admin_can_update_area(): void
    {
        $this->actingAsAdmin();
        $area = Area::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('areas.update', $area), ['name' => 'New']);

        $response->assertRedirect();
        $this->assertSame('New', $area->fresh()->name);
    }

    #[Test]
    public function admin_can_duplicate_area(): void
    {
        $this->actingAsAdmin();
        $area = Area::factory()->create();
        $before = Area::query()->count();

        $response = $this->post(route('areas.duplicate', $area));

        $response->assertRedirect();
        $this->assertGreaterThan($before, Area::query()->count());
    }

    #[Test]
    public function admin_can_destroy_area(): void
    {
        $this->actingAsAdmin();
        $area = Area::factory()->create();

        $response = $this->delete('/areas/' . $area->id);

        $response->assertRedirect();
        $this->assertSoftDeleted('areas', ['id' => $area->id]);
    }

    #[Test]
    public function admin_can_view_trashed_areas(): void
    {
        $this->actingAsAdmin();

        $this->get(route('areas.trashed'))->assertOk();
    }

    #[Test]
    public function admin_can_restore_trashed_area(): void
    {
        $this->actingAsAdmin();
        $area = Area::factory()->create();
        $area->delete();

        $response = $this->patch(route('areas.restore', $area->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('areas', ['id' => $area->id, 'deleted_at' => null]);
    }

    #[Test]
    public function admin_can_force_delete_area(): void
    {
        $this->actingAsAdmin();
        $area = Area::factory()->create();
        $area->delete();

        $response = $this->delete(route('areas.force', $area->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('areas', ['id' => $area->id]);
    }

    #[Test]
    public function admin_can_force_delete_all_trashed_areas(): void
    {
        $this->actingAsAdmin();
        $a = Area::factory()->create();
        $b = Area::factory()->create();
        $a->delete();
        $b->delete();

        $response = $this->delete(route('areas.force.all'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('areas', ['id' => $a->id]);
        $this->assertDatabaseMissing('areas', ['id' => $b->id]);
    }

    #[Test]
    public function force_delete_returns_404_for_unknown_id(): void
    {
        $this->actingAsAdmin();

        $this->delete(route('areas.force', 999999))->assertNotFound();
    }
}
