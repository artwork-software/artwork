<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventCollisionService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\User\Models\User;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventStoreTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_event(): void
    {
        $this->postJson(route('events.store'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_storing_event_with_empty_payload_returns_validation_error(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('events.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start', 'end', 'projectIdMandatory', 'creatingProject', 'eventNameMandatory', 'eventTypeId']);
    }

    #[Test]
    public function admin_storing_event_with_end_before_start_fails_validation(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 09:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end']);
    }

    #[Test]
    public function admin_storing_event_with_invalid_room_fails_validation(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 12:00',
            'roomId' => PHP_INT_MAX,
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['roomId']);
    }

    #[Test]
    public function admin_storing_event_with_invalid_event_type_fails_validation(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => PHP_INT_MAX,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['eventTypeId']);
    }

    #[Test]
    public function admin_storing_event_with_project_mandatory_requires_project_id(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 12:00',
            'projectIdMandatory' => true,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['projectId']);
    }

    #[Test]
    public function admin_storing_event_with_event_name_mandatory_requires_event_name(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => true,
            'eventTypeId' => $eventType->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['eventName']);
    }

    #[Test]
    public function admin_can_store_minimal_event_with_room(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Smoke event',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('events', [
            'event_type_id' => $eventType->id,
            'name' => 'Smoke event',
            'room_id' => $room->id,
        ]);
    }

    #[Test]
    public function user_without_permission_cannot_store_event(): void
    {
        $this->actingAs(User::factory()->create());
        $eventType = EventType::factory()->create();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
        ]);

        // EventPolicy::create returns false for plain user without permission
        $response->assertStatus(403);
    }

    #[Test]
    public function request_only_user_always_creates_a_room_request_even_if_client_requests_direct_booking(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $roomOwner = User::factory()->create();
        $roomAdmin = User::factory()->create();
        $room = Room::factory()->create([
            'user_id' => $roomOwner->id,
            'everyone_can_book' => false,
        ]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $eventType = EventType::factory()->create();
        $this->mock(RoomRequestNotificationService::class)
            ->shouldReceive('notifyRoomAdmins')
            ->once()
            ->with(Mockery::type(Event::class));
        $this->mock(EventCollisionService::class)
            ->shouldReceive('adjoiningCollision')
            ->once()
            ->andReturn([])
            ->getMock()
            ->shouldReceive('getCollision')
            ->once()
            ->andReturn(Event::query()->whereKey(-1));
        $response = $this->postJson(route('events.store'), $this->validPayload($eventType, $room, [
            'isOption' => false,
            'showProjectPeriodInCalendar' => true,
        ]));

        $response->assertRedirect();
        $event = Event::query()->latest('id')->firstOrFail();
        $this->assertSame($requester->id, $event->user_id);
        $this->assertTrue($event->occupancy_option);
    }

    #[Test]
    public function invalid_room_request_flag_is_rejected(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $eventType = EventType::factory()->create();

        $this->postJson(route('events.store'), $this->validPayload($eventType, $room, [
            'isOption' => 'false',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isOption');
    }

    #[Test]
    public function admin_can_store_event_associated_with_existing_project(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();
        $project = Project::factory()->create();
        $room = Room::factory()->create();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 12:00',
            'projectIdMandatory' => true,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'projectId' => $project->id,
            'roomId' => $room->id,
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('events', [
            'project_id' => $project->id,
            'event_type_id' => $eventType->id,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(EventType $eventType, Room $room, array $overrides = []): array
    {
        return array_replace([
            'start' => '2026-05-10 10:00',
            'end' => '2026-05-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Room request',
            'isOption' => true,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
        ], $overrides);
    }
}
