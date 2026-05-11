<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Sector\Models\Sector;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SectorControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('sectors.store'), ['name' => 'X', 'color' => '#fff'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('sectors.store'), [
            'name' => 'Music',
            'color' => '#abcdef',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sectors', ['name' => 'Music']);
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $sector = Sector::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('sectors.update', $sector), [
            'name' => 'New',
            'color' => '#222',
        ]);

        $response->assertOk();
        $this->assertSame('New', $sector->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $sector = Sector::factory()->create();

        $response = $this->delete('/sectors/' . $sector->id);

        $response->assertRedirect();
        $this->assertSoftDeleted('sectors', ['id' => $sector->id]);
    }

    #[Test]
    public function admin_can_restore_trashed_sector(): void
    {
        $this->actingAsAdmin();
        $sector = Sector::factory()->create();
        $sector->delete();

        $response = $this->patch('/sectors/' . $sector->id . '/restore');

        $response->assertRedirect();
        $this->assertDatabaseHas('sectors', ['id' => $sector->id, 'deleted_at' => null]);
    }

    #[Test]
    public function admin_can_force_delete(): void
    {
        $this->actingAsAdmin();
        $sector = Sector::factory()->create();
        $sector->delete();

        $response = $this->delete(route('sectors.force', $sector->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('sectors', ['id' => $sector->id]);
    }
}
