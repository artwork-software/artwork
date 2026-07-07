<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class RoomControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_room(): void
    {
        $this->post(route('rooms.store'), ['name' => 'Stage'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_room(): void
    {
        $admin = $this->actingAsAdmin();
        $area = Area::factory()->create();

        $response = $this->post(route('rooms.store'), [
            'name' => 'Main Stage',
            'description' => 'Big stage',
            'temporary' => false,
            'area_id' => $area->id,
            'user_id' => $admin->id,
            'everyone_can_book' => false,
            'relevant_for_disposition' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rooms', ['name' => 'Main Stage', 'area_id' => $area->id]);
    }

    #[Test]
    public function guest_cannot_show_room(): void
    {
        $room = Room::factory()->create();

        $this->get(route('rooms.show', $room))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_show_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();

        $this->get(route('rooms.show', $room))->assertOk();
    }

    #[Test]
    public function user_without_can_view_room_is_redirected_back(): void
    {
        $this->actingAs(User::factory()->create());
        $room = Room::factory()->create();

        // CanViewRoom middleware: non-admin without ROOM_UPDATE and not a room admin → redirect back
        $this->get(route('rooms.show', $room))->assertRedirect();
    }

    #[Test]
    public function guest_cannot_view_move_rooms_page(): void
    {
        $this->get(route('rooms.move'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_move_rooms_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('rooms.move'))->assertOk();
    }

    #[Test]
    public function guest_cannot_update_order(): void
    {
        $this->put('/rooms/order', ['rooms' => []])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_room_order(): void
    {
        $this->actingAsAdmin();
        $room1 = Room::factory()->create(['order' => 1]);
        $room2 = Room::factory()->create(['order' => 2]);

        $response = $this->put('/rooms/order', [
            'rooms' => [
                ['id' => $room1->id, 'order' => 2],
                ['id' => $room2->id, 'order' => 1],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(2, (int) $room1->fresh()->order);
        $this->assertSame(1, (int) $room2->fresh()->order);
    }

    #[Test]
    public function admin_can_update_room_position_via_order_new(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();

        $response = $this->post(route('rooms.order.new'), [
            'rooms' => [['id' => $room->id, 'position' => 99]],
        ]);

        $response->assertRedirect();
        $this->assertSame(99, (int) $room->fresh()->position);
    }

    #[Test]
    public function guest_cannot_update_room(): void
    {
        $room = Room::factory()->create();

        $this->patch(route('rooms.update', $room), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('rooms.update', $room), [
            'name' => 'Updated Name',
            'description' => 'desc',
            'temporary' => false,
            'everyone_can_book' => false,
            'relevant_for_disposition' => false,
        ]);

        $response->assertRedirect();
        $this->assertSame('Updated Name', $room->fresh()->name);
    }

    #[Test]
    public function guest_cannot_duplicate_room(): void
    {
        $room = Room::factory()->create();

        $this->post(route('rooms.duplicate', $room))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_duplicate_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $before = Room::query()->count();

        $response = $this->post(route('rooms.duplicate', $room));

        $response->assertRedirect();
        $this->assertGreaterThan($before, Room::query()->count());
    }

    #[Test]
    public function guest_cannot_destroy_room(): void
    {
        $room = Room::factory()->create();

        $this->delete('/rooms/' . $room->id)
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_soft_delete_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();

        $response = $this->delete('/rooms/' . $room->id);

        $response->assertRedirect();
        $this->assertSoftDeleted('rooms', ['id' => $room->id]);
    }

    #[Test]
    public function admin_can_view_trashed_rooms(): void
    {
        $this->actingAsAdmin();

        $this->get(route('rooms.trashed'))->assertOk();
    }

    #[Test]
    public function guest_cannot_view_trashed_rooms(): void
    {
        $this->get(route('rooms.trashed'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_restore_trashed_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $room->delete();

        $response = $this->patch(route('rooms.restore', $room->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'deleted_at' => null]);
    }

    #[Test]
    public function restore_returns_404_for_unknown_id(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('rooms.restore', 999999))
            ->assertNotFound();
    }

    #[Test]
    public function admin_can_force_delete_trashed_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $room->delete();

        $response = $this->delete(route('rooms.force', $room->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    #[Test]
    public function force_delete_returns_404_for_unknown_id(): void
    {
        $this->actingAsAdmin();

        $this->delete(route('rooms.force', 999999))
            ->assertNotFound();
    }

    #[Test]
    public function admin_can_force_delete_all_trashed_rooms(): void
    {
        $this->actingAsAdmin();
        $a = Room::factory()->create();
        $b = Room::factory()->create();
        $a->delete();
        $b->delete();

        $response = $this->delete(route('rooms.force.all'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('rooms', ['id' => $a->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $b->id]);
    }

    #[Test]
    public function guest_can_search_rooms_via_public_api(): void
    {
        // /api/room/search is not auth-protected (public API endpoint)
        Room::factory()->create(['name' => 'GuestStage']);

        $response = $this->post(route('room.search'), ['search' => 'GuestStage']);

        $response->assertOk();
    }

    #[Test]
    public function admin_can_search_rooms_via_api(): void
    {
        $this->actingAsAdmin();
        Room::factory()->create(['name' => 'Findable Stage']);

        $response = $this->post(route('room.search'), ['search' => 'Findable']);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Findable Stage']);
    }

    #[Test]
    public function admin_can_query_collisions_count(): void
    {
        $this->actingAsAdmin();
        Room::factory()->create();

        $response = $this->post(route('collisions.room'), [
            'params' => [
                'start' => '2026-01-01T10:00:00+00:00',
                'end' => '2026-01-01T11:00:00+00:00',
                'currentEventId' => null,
            ],
        ]);

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    #[Test]
    public function admin_can_get_all_day_free_rooms(): void
    {
        $this->actingAsAdmin();
        Room::factory()->count(2)->create();

        $response = $this->getJson(route('rooms.free', [
            'start' => '2026-01-01',
            'end' => '2026-01-02',
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['rooms']);
    }
}
