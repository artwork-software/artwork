<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventMultiCellTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_create_events_in_cells(): void
    {
        $this->postJson(route('events.multi-cell.create'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function user_without_bulk_creation_permission_cannot_create_events_in_cells(): void
    {
        $this->actingAsUserWith([]);
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('events.multi-cell.create'), [
            'cells' => [['day' => '2026-08-03', 'room_id' => $room->id]],
            'event_type_id' => $eventType->id,
        ])
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_create_event_in_multiple_cells(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();
        $roomA = Room::factory()->create();
        $roomB = Room::factory()->create();

        $response = $this->postJson(route('events.multi-cell.create'), [
            'cells' => [
                ['day' => '2026-08-03', 'room_id' => $roomA->id],
                ['day' => '2026-08-05', 'room_id' => $roomB->id],
            ],
            'event_type_id' => $eventType->id,
            'name' => 'Zellen-Termin',
            'start_time' => '10:00',
            'end_time' => '12:30',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('events', [
            'eventName' => 'Zellen-Termin',
            'room_id' => $roomA->id,
            'start_time' => '2026-08-03 10:00:00',
            'end_time' => '2026-08-03 12:30:00',
        ]);
        $this->assertDatabaseHas('events', [
            'eventName' => 'Zellen-Termin',
            'room_id' => $roomB->id,
            'start_time' => '2026-08-05 10:00:00',
            'end_time' => '2026-08-05 12:30:00',
        ]);
    }

    #[Test]
    public function creating_without_times_results_in_all_day_events(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create();

        $this->postJson(route('events.multi-cell.create'), [
            'cells' => [
                ['day' => '2026-08-03', 'room_id' => $room->id],
            ],
            'event_type_id' => $eventType->id,
        ])->assertSuccessful();

        $event = Event::query()
            ->where('room_id', $room->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertTrue((bool) $event->allDay);
        $this->assertSame('2026-08-03', $event->start_time->toDateString());
        $this->assertSame('2026-08-03', $event->end_time->toDateString());
    }

    #[Test]
    public function create_requires_at_least_one_cell_and_an_event_type(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('events.multi-cell.create'), ['cells' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cells', 'event_type_id']);
    }

    #[Test]
    public function guest_cannot_duplicate_events_to_cells(): void
    {
        $this->postJson(route('events.multi-cell.duplicate'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function request_only_user_cannot_create_direct_bulk_project_events(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $project = Project::factory()->create();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('events.bulk.store', $project), [
            'events' => [[
                'name' => 'Direkter Termin',
                'day' => '2026-08-03',
                'room' => ['id' => $room->id],
                'type' => ['id' => $eventType->id],
            ]],
        ])->assertForbidden();

        $this->assertDatabaseMissing('events', ['eventName' => 'Direkter Termin']);
    }

    #[Test]
    public function user_without_update_rights_cannot_duplicate_foreign_events(): void
    {
        $event = Event::factory()->create();
        $room = Room::factory()->create();
        $this->actingAsUserWith([]);

        $this->postJson(route('events.multi-cell.duplicate'), [
            'events' => [$event->id],
            'cells' => [
                ['day' => '2026-08-03', 'room_id' => $room->id],
            ],
        ])->assertForbidden();
    }

    #[Test]
    public function admin_can_duplicate_events_into_multiple_cells(): void
    {
        $this->actingAsAdmin();
        $roomA = Room::factory()->create();
        $roomB = Room::factory()->create();
        $eventA = Event::factory()->create([
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-01 12:00:00',
            'allDay' => false,
        ]);
        $eventB = Event::factory()->create([
            'start_time' => '2026-08-01 19:00:00',
            'end_time' => '2026-08-01 22:00:00',
            'allDay' => false,
        ]);

        $countBefore = Event::query()->count();

        $this->postJson(route('events.multi-cell.duplicate'), [
            'events' => [$eventA->id, $eventB->id],
            'cells' => [
                ['day' => '2026-08-10', 'room_id' => $roomA->id],
                ['day' => '2026-08-12', 'room_id' => $roomB->id],
            ],
        ])->assertSuccessful();

        $this->assertSame($countBefore + 4, Event::query()->count());
        $this->assertDatabaseHas('events', [
            'room_id' => $roomA->id,
            'start_time' => '2026-08-10 10:00:00',
            'end_time' => '2026-08-10 12:00:00',
        ]);
        $this->assertDatabaseHas('events', [
            'room_id' => $roomB->id,
            'start_time' => '2026-08-12 19:00:00',
            'end_time' => '2026-08-12 22:00:00',
        ]);
    }

    #[Test]
    public function creating_in_cells_with_planning_flag_creates_planning_events(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create();

        $this->postJson(route('events.multi-cell.create'), [
            'cells' => [
                ['day' => '2026-08-03', 'room_id' => $room->id],
            ],
            'event_type_id' => $eventType->id,
            'name' => 'Geplanter Zellen-Termin',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'is_planning' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('events', [
            'eventName' => 'Geplanter Zellen-Termin',
            'room_id' => $room->id,
            'is_planning' => true,
        ]);
    }

    #[Test]
    public function planning_editor_cannot_create_regular_events_in_cells(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_EDIT_PLANNING_CALENDAR]);
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('events.multi-cell.create'), [
            'cells' => [['day' => '2026-08-03', 'room_id' => $room->id]],
            'event_type_id' => $eventType->id,
            'is_planning' => false,
        ])->assertForbidden();
    }

    #[Test]
    public function planning_editor_can_create_planning_events_in_cells(): void
    {
        $this->actingAsUserWith([PermissionEnum::CAN_EDIT_PLANNING_CALENDAR]);
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('events.multi-cell.create'), [
            'cells' => [['day' => '2026-08-03', 'room_id' => $room->id]],
            'event_type_id' => $eventType->id,
            'is_planning' => true,
        ])->assertSuccessful();
    }

    #[Test]
    public function source_owner_cannot_duplicate_regular_event_into_closed_room_without_create_rights(): void
    {
        $user = $this->actingAsUserWith([]);
        $source = Event::factory()->create(['user_id' => $user->id, 'is_planning' => false]);
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('events.multi-cell.duplicate'), [
            'events' => [$source->id],
            'cells' => [['day' => '2026-08-10', 'room_id' => $targetRoom->id]],
        ])->assertForbidden();
    }

    #[Test]
    public function duplicate_cells_are_rejected_before_any_event_is_created(): void
    {
        $this->actingAsAdmin();
        $source = Event::factory()->create();
        $targetRoom = Room::factory()->create();
        $countBefore = Event::query()->count();

        $this->postJson(route('events.multi-cell.duplicate'), [
            'events' => [$source->id],
            'cells' => [
                ['day' => '2026-08-10', 'room_id' => $targetRoom->id],
                ['day' => '2026-08-10', 'room_id' => $targetRoom->id],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('cells');

        $this->assertSame($countBefore, Event::query()->count());
    }

    #[Test]
    public function duplicating_into_cells_with_planning_flag_creates_planning_events(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-01 12:00:00',
            'allDay' => false,
            'is_planning' => false,
        ]);

        $this->postJson(route('events.multi-cell.duplicate'), [
            'events' => [$event->id],
            'cells' => [
                ['day' => '2026-08-10', 'room_id' => $room->id],
            ],
            'is_planning' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'start_time' => '2026-08-10 10:00:00',
            'is_planning' => true,
        ]);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'is_planning' => false,
        ]);
    }

    #[Test]
    public function duplicating_into_cells_without_planning_flag_keeps_source_state(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-01 12:00:00',
            'allDay' => false,
            'is_planning' => false,
        ]);

        $this->postJson(route('events.multi-cell.duplicate'), [
            'events' => [$event->id],
            'cells' => [
                ['day' => '2026-08-10', 'room_id' => $room->id],
            ],
        ])->assertSuccessful();

        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'start_time' => '2026-08-10 10:00:00',
            'is_planning' => false,
        ]);
    }

    #[Test]
    public function duplicating_multi_day_events_preserves_duration(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'start_time' => '2026-08-01 20:00:00',
            'end_time' => '2026-08-03 02:00:00',
            'allDay' => false,
        ]);

        $this->postJson(route('events.multi-cell.duplicate'), [
            'events' => [$event->id],
            'cells' => [
                ['day' => '2026-08-10', 'room_id' => $room->id],
            ],
        ])->assertSuccessful();

        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'start_time' => '2026-08-10 20:00:00',
            'end_time' => '2026-08-12 02:00:00',
        ]);
    }
}
