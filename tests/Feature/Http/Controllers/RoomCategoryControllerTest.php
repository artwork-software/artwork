<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Room\Models\RoomCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class RoomCategoryControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('room_categories.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('room_categories.store'), ['name' => 'Music Room']);

        $response->assertOk();
        $this->assertDatabaseHas('room_categories', ['name' => 'Music Room']);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $cat = RoomCategory::query()->forceCreate(['name' => 'X']);

        $response = $this->delete(route('room_categories.destroy', $cat));

        $response->assertRedirect();
        $this->assertDatabaseMissing('room_categories', ['id' => $cat->id]);
    }
}
