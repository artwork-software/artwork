<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventBulkActionsTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_bulk_delete_events(): void
    {
        $this->deleteJson(route('event.bulk.multi-edit.delete'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function requester_cannot_bulk_accept_own_room_request(): void
    {
        $requester = $this->actingAsUserWith(PermissionEnum::EVENT_REQUEST->value);
        $event = Event::factory()->create([
            'user_id' => $requester->id,
            'occupancy_option' => true,
        ]);

        $this->putJson(route('events.bulk-accept'), ['eventIds' => [$event->id]])
            ->assertForbidden();

        $this->assertTrue($event->fresh()->occupancy_option);
    }

    #[Test]
    public function room_admin_can_bulk_accept_pending_room_request(): void
    {
        $roomAdmin = User::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'occupancy_option' => true,
        ]);
        $this->actingAs($roomAdmin);

        $this->put(route('events.bulk-accept'), ['eventIds' => [$event->id]])
            ->assertRedirect();

        $this->assertFalse($event->fresh()->occupancy_option);
    }

    #[Test]
    public function bulk_room_request_decision_requires_at_least_one_event(): void
    {
        $this->actingAsAdmin();

        $this->putJson(route('events.bulk-accept'), ['eventIds' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('eventIds');
    }

    #[Test]
    public function admin_can_bulk_delete_events(): void
    {
        $this->actingAsAdmin();
        $eventA = Event::factory()->create();
        $eventB = Event::factory()->create();

        $response = $this->deleteJson(route('event.bulk.multi-edit.delete'), [
            'eventIds' => [$eventA->id, $eventB->id],
        ]);

        $response->assertSuccessful();
        $this->assertSoftDeleted('events', ['id' => $eventA->id]);
        $this->assertSoftDeleted('events', ['id' => $eventB->id]);
    }

    #[Test]
    public function admin_bulk_delete_with_empty_payload_is_noop(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->deleteJson(route('event.bulk.multi-edit.delete'), [
            'eventIds' => [],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
    }

    #[Test]
    public function guest_cannot_bulk_multi_edit_events(): void
    {
        $this->postJson(route('events.bulk-multi-edit'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_bulk_multi_edit_events_with_empty_payload(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('events.bulk-multi-edit'), ['eventIds' => []]);

        $response->assertSuccessful();
    }

    #[Test]
    public function guest_cannot_save_multi_edit(): void
    {
        $this->patchJson(route('multi-edit.save'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_post_multi_edit_delete(): void
    {
        $this->postJson(route('multi-edit.delete'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_post_multi_edit_delete_with_empty_events_list(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('multi-edit.delete'), ['events' => []]);

        $response->assertSuccessful();
    }

    #[Test]
    public function admin_can_post_multi_edit_delete_with_event_ids(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->postJson(route('multi-edit.delete'), [
            'events' => [$event->id],
        ]);

        $response->assertSuccessful();
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    #[Test]
    public function guest_cannot_save_multi_duplicate(): void
    {
        $this->patchJson(route('multi-duplicate.save'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_bulk_project_event_store(): void
    {
        $project = Project::factory()->create();

        $this->postJson(route('events.bulk.store', $project), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_bulk_project_event_store_with_empty_events_redirects(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post(route('events.bulk.store', $project), ['events' => []]);

        $response->assertRedirect();
    }

    #[Test]
    public function guest_cannot_update_single_bulk_event(): void
    {
        $event = Event::factory()->create();

        $this->patchJson(route('event.update.single.bulk', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_create_single_bulk_event(): void
    {
        $project = Project::factory()->create();

        $this->postJson(route('event.store.bulk.single', $project), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function user_without_permission_cannot_change_room_via_single_bulk_update(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_REQUEST->value);
        $event = Event::factory()->create();
        $originalRoomId = $event->room_id;
        $otherRoom = Room::factory()->create();

        $response = $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => [
                'name' => $event->name,
                'day' => $event->start_time->format('Y-m-d'),
                'type' => ['id' => $event->event_type_id],
                'room' => ['id' => $otherRoom->id],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'room_id' => $originalRoomId]);
    }

    #[Test]
    public function user_with_create_events_permission_can_change_room_via_single_bulk_update(): void
    {
        $this->actingAsUserWith(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $event = Event::factory()->create();
        $otherRoom = Room::factory()->create();

        $response = $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => [
                'name' => $event->name,
                'day' => $event->start_time->format('Y-m-d'),
                'type' => ['id' => $event->event_type_id],
                'room' => ['id' => $otherRoom->id],
            ],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'room_id' => $otherRoom->id]);
    }

    #[Test]
    public function user_without_permission_cannot_update_event_description(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_REQUEST->value);
        $event = Event::factory()->create(['description' => 'original']);

        $this->patchJson(route('event.update.description', $event), ['description' => 'changed'])
            ->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'description' => 'original']);
    }

    #[Test]
    public function user_without_permission_cannot_create_single_bulk_event(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_REQUEST->value);
        $project = Project::factory()->create();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('event.store.bulk.single', $project), [
            'event' => [
                'type' => ['id' => $eventType->id],
                'room' => ['id' => $room->id],
            ],
        ])
            ->assertForbidden();
    }

    #[Test]
    public function user_without_permission_cannot_bulk_project_event_store(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_REQUEST->value);
        $project = Project::factory()->create();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('events.bulk.store', $project), [
            'events' => [[
                'type' => ['id' => $eventType->id],
                'room' => ['id' => $room->id],
            ]],
        ])
            ->assertForbidden();
    }

    #[Test]
    public function user_without_permission_cannot_bulk_multi_edit_events(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_REQUEST->value);
        $event = Event::factory()->create();
        $otherRoom = Room::factory()->create();

        $this->postJson(route('events.bulk-multi-edit'), [
            'eventIds' => [$event->id],
            'selectedRoom' => ['id' => $otherRoom->id],
        ])->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'room_id' => $event->room_id]);
    }

    #[Test]
    public function user_without_permission_cannot_bulk_delete_events(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_REQUEST->value);
        $event = Event::factory()->create();

        $this->deleteJson(route('event.bulk.multi-edit.delete'), ['eventIds' => [$event->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
    }

    #[Test]
    public function guest_cannot_view_standard_event_values(): void
    {
        $this->get(route('event.standard.values'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_standard_event_values(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('event.standard.values'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_save_standard_event_values(): void
    {
        $this->patchJson(route('event.standard.values.update'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_save_standard_event_values(): void
    {
        $this->actingAsAdmin();

        $response = $this->patchJson(route('event.standard.values.update'), [
            'event_time_length_minutes' => 60,
        ]);

        $response->assertSuccessful();
    }

    #[Test]
    public function guest_cannot_commit_shifts(): void
    {
        $this->postJson(route('shifts.commit'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_get_shift_plan_crafts(): void
    {
        $response = $this->getJson(route('shifts.crafts'));

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    #[Test]
    public function admin_can_get_shift_plan_crafts(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('shifts.crafts'));

        $response->assertOk();
        $response->assertJsonStructure(['crafts']);
    }

    #[Test]
    public function guest_cannot_delete_old_notifications(): void
    {
        $this->postJson('/notifications/abc-key', [])
            ->assertUnauthorized();
    }
}
