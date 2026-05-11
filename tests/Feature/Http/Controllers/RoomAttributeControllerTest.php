<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class RoomAttributeControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_room_attribute(): void
    {
        $this->post(route('room_attribute.store'), ['name' => 'Foo'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_room_attribute(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('room_attribute.store'), [
            'name' => 'Stage Lighting',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('room_attributes', [
            'name' => 'Stage Lighting',
        ]);
    }

    #[Test]
    public function admin_can_destroy_room_attribute(): void
    {
        $this->actingAsAdmin();
        $attribute = RoomAttribute::query()->forceCreate([
            'name' => 'Surround Sound',
        ]);

        $response = $this->delete(route('room_attribute.destroy', $attribute));

        $response->assertRedirect();
        $this->assertDatabaseMissing('room_attributes', [
            'id' => $attribute->id,
        ]);
    }

    #[Test]
    public function authenticated_user_can_store_room_attribute(): void
    {
        // Controller has no policy/permission — any auth'd user can store.
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('room_attribute.store'), [
            'name' => 'Plain User Attr',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('room_attributes', [
            'name' => 'Plain User Attr',
        ]);
    }
}
