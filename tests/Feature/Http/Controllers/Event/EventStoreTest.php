<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
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
}
