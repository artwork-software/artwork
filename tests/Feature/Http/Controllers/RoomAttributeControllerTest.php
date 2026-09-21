<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
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
    public function user_without_room_permission_cannot_store_room_attribute(): void
    {
        // Sicherheits-Audit 21.09.2026: Raumattribute sind Stammdaten und brauchen das Raumrecht.
        $this->actingAs(User::factory()->create());

        $this->post(route('room_attribute.store'), [
            'name' => 'Plain User Attr',
        ])->assertForbidden();

        $this->assertDatabaseMissing('room_attributes', [
            'name' => 'Plain User Attr',
        ]);
    }

    #[Test]
    public function user_with_room_permission_can_store_room_attribute(): void
    {
        $this->actingAsUserWith([PermissionEnum::ROOM_UPDATE]);

        $response = $this->post(route('room_attribute.store'), [
            'name' => 'Room Manager Attr',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('room_attributes', [
            'name' => 'Room Manager Attr',
        ]);
    }
}
