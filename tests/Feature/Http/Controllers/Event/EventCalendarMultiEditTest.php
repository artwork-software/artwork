<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Kalender-Multiedit-Endpunkte (multi-edit.delete, multi-duplicate.save) —
 * insbesondere Autorisierung und das Planning-Flag des Planungskalenders.
 */
final class EventCalendarMultiEditTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_multi_edit_delete_events(): void
    {
        $this->postJson(route('multi-edit.delete'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function user_without_delete_rights_cannot_multi_edit_delete_foreign_events(): void
    {
        $event = Event::factory()->create();
        $this->actingAsUserWith([]);

        $this->postJson(route('multi-edit.delete'), ['events' => [$event->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
    }

    #[Test]
    public function planning_editor_can_multi_edit_delete_planning_events(): void
    {
        $event = Event::factory()->create(['is_planning' => true]);
        $this->actingAsUserWith(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value);

        $this->postJson(route('multi-edit.delete'), ['events' => [$event->id]])
            ->assertSuccessful();

        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    #[Test]
    public function planning_editor_cannot_multi_edit_delete_confirmed_foreign_events(): void
    {
        $event = Event::factory()->create(['is_planning' => false]);
        $this->actingAsUserWith(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value);

        $this->postJson(route('multi-edit.delete'), ['events' => [$event->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
    }

    #[Test]
    public function admin_can_multi_edit_delete_events(): void
    {
        $this->actingAsAdmin();
        $eventA = Event::factory()->create();
        $eventB = Event::factory()->create(['is_planning' => true]);

        $this->postJson(route('multi-edit.delete'), ['events' => [$eventA->id, $eventB->id]])
            ->assertSuccessful();

        $this->assertSoftDeleted('events', ['id' => $eventA->id]);
        $this->assertSoftDeleted('events', ['id' => $eventB->id]);
    }

    #[Test]
    public function multi_duplicate_with_planning_flag_creates_planning_duplicates(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-01 12:00:00',
            'allDay' => false,
            'is_planning' => false,
        ]);

        $countBefore = Event::query()->count();

        $this->patchJson(route('multi-duplicate.save'), [
            'events' => [$event->id],
            'newRoomId' => null,
            'calculationType' => 1,
            'value' => 1,
            'type' => 2,
            'date' => null,
            'isPlanning' => true,
        ])->assertSuccessful();

        $this->assertSame($countBefore + 1, Event::query()->count());
        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'start_time' => '2026-08-02 10:00:00',
            'end_time' => '2026-08-02 12:00:00',
            'is_planning' => true,
        ]);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'is_planning' => false,
        ]);
    }

    #[Test]
    public function multi_duplicate_without_planning_flag_keeps_source_state(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => '2026-08-01 10:00:00',
            'end_time' => '2026-08-01 12:00:00',
            'allDay' => false,
            'is_planning' => false,
        ]);

        $this->patchJson(route('multi-duplicate.save'), [
            'events' => [Event::query()->latest('id')->value('id')],
            'newRoomId' => null,
            'calculationType' => 1,
            'value' => 1,
            'type' => 2,
            'date' => null,
        ])->assertSuccessful();

        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'start_time' => '2026-08-02 10:00:00',
            'is_planning' => false,
        ]);
    }
}
