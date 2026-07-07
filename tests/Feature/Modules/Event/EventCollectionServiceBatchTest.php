<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regression für den Batch-Umbau von EventCollectionService::collectEventsForRoomsOnSpecificDays
 * (eine Query über alle Räume + Zeitraum statt Query pro Raum × Tag) sowie die
 * withCount/withExists-Aggregate der MinimalCalendarEventResource.
 */
final class EventCollectionServiceBatchTest extends FeatureTestCase
{
    private function service(): EventCollectionService
    {
        return app(EventCollectionService::class);
    }

    private function makeEvent(Room $room, string $start, string $end, array $attributes = []): Event
    {
        return Event::factory()->create(array_merge([
            'room_id' => $room->id,
            'start_time' => $start,
            'end_time' => $end,
        ], $attributes));
    }

    #[Test]
    public function groups_events_by_day_and_room_including_multi_day_overlap(): void
    {
        $roomA = Room::factory()->create();
        $roomB = Room::factory()->create();

        $singleDay = $this->makeEvent($roomA, '2026-08-10 10:00:00', '2026-08-10 12:00:00');
        $multiDay = $this->makeEvent($roomA, '2026-08-10 22:00:00', '2026-08-12 02:00:00');
        $inRoomB = $this->makeEvent($roomB, '2026-08-11 09:00:00', '2026-08-11 10:00:00');

        $days = ['2026-08-10', '2026-08-11', '2026-08-12'];
        $result = $this->service()->collectEventsForRoomsOnSpecificDays(
            [$roomA->id, $roomB->id],
            $days,
            null
        );

        $this->assertSame($days, array_keys($result));
        foreach ($days as $day) {
            $this->assertSame(
                [$roomA->id, $roomB->id],
                array_keys($result[$day]),
                "Beide Räume müssen für {$day} enthalten sein"
            );
        }

        $idsFor = static fn (array $events): array => array_column($events, 'id');

        $this->assertEqualsCanonicalizing(
            [$singleDay->id, $multiDay->id],
            $idsFor($result['2026-08-10'][$roomA->id])
        );
        // Mehrtägiges Event erscheint an jedem überlappten Tag
        $this->assertSame([$multiDay->id], $idsFor($result['2026-08-11'][$roomA->id]));
        $this->assertSame([$multiDay->id], $idsFor($result['2026-08-12'][$roomA->id]));

        $this->assertSame([$inRoomB->id], $idsFor($result['2026-08-11'][$roomB->id]));
        $this->assertSame([], $result['2026-08-10'][$roomB->id]);
    }

    #[Test]
    public function excludes_deleted_events_and_rooms_not_requested(): void
    {
        $roomA = Room::factory()->create();
        $otherRoom = Room::factory()->create();

        $kept = $this->makeEvent($roomA, '2026-08-10 10:00:00', '2026-08-10 12:00:00');
        $deleted = $this->makeEvent($roomA, '2026-08-10 13:00:00', '2026-08-10 14:00:00');
        $deleted->delete();
        $this->makeEvent($otherRoom, '2026-08-10 10:00:00', '2026-08-10 12:00:00');

        $result = $this->service()->collectEventsForRoomsOnSpecificDays([$roomA->id], ['2026-08-10'], null);

        $this->assertSame([$kept->id], array_column($result['2026-08-10'][$roomA->id], 'id'));
        $this->assertArrayNotHasKey($otherRoom->id, $result['2026-08-10']);
    }

    #[Test]
    public function returns_empty_array_when_rooms_or_days_are_empty(): void
    {
        $this->assertSame([], $this->service()->collectEventsForRoomsOnSpecificDays([], ['2026-08-10'], null));
        $this->assertSame([], $this->service()->collectEventsForRoomsOnSpecificDays([1], [], null));
    }

    #[Test]
    public function accepts_string_room_ids_like_request_input(): void
    {
        $room = Room::factory()->create();
        $event = $this->makeEvent($room, '2026-08-10 10:00:00', '2026-08-10 12:00:00');

        // Request-Arrays liefern IDs als Strings — Gruppierung muss trotzdem matchen
        $result = $this->service()->collectEventsForRoomsOnSpecificDays(
            [(string) $room->id],
            ['2026-08-10'],
            null
        );

        $this->assertSame([$event->id], array_column($result['2026-08-10'][(string) $room->id], 'id'));
    }

    #[Test]
    public function filters_by_project_when_given(): void
    {
        $room = Room::factory()->create();
        $project = Project::factory()->create();

        $inProject = $this->makeEvent($room, '2026-08-10 10:00:00', '2026-08-10 12:00:00', [
            'project_id' => $project->id,
        ]);
        $this->makeEvent($room, '2026-08-10 13:00:00', '2026-08-10 14:00:00');

        $result = $this->service()->collectEventsForRoomsOnSpecificDays(
            [$room->id],
            ['2026-08-10'],
            null,
            $project
        );

        $this->assertSame([$inProject->id], array_column($result['2026-08-10'][$room->id], 'id'));
    }

    #[Test]
    public function resource_payload_contains_shift_counts_and_timeline_flag(): void
    {
        $room = Room::factory()->create();
        $withTimeline = $this->makeEvent($room, '2026-08-10 10:00:00', '2026-08-10 12:00:00');
        $withoutTimeline = $this->makeEvent($room, '2026-08-10 13:00:00', '2026-08-10 14:00:00');

        Timeline::factory()->create(['event_id' => $withTimeline->id]);

        $craft = Craft::factory()->create(['name' => 'Bühne', 'abbreviation' => 'BÜ']);
        $shift = Shift::factory()->create([
            'event_id' => $withTimeline->id,
            'craft_id' => $craft->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
        ]);
        $qualification = ShiftQualification::factory()->create();
        ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 3,
        ]);
        foreach (User::factory()->count(2)->create() as $worker) {
            $shift->users()->attach($worker->id, [
                'shift_qualification_id' => $qualification->id,
                'shift_count' => 1,
            ]);
        }

        $result = $this->service()->collectEventsForRoomsOnSpecificDays([$room->id], ['2026-08-10'], null);
        $events = collect($result['2026-08-10'][$room->id])->keyBy('id');

        $this->assertTrue($events[$withTimeline->id]['hasTimelines']);
        $this->assertFalse($events[$withoutTimeline->id]['hasTimelines']);

        $shiftPayload = $events[$withTimeline->id]['shifts'][0];
        $this->assertSame(2, $shiftPayload['worker_count']);
        $this->assertSame(3, $shiftPayload['max_worker_count']);
        $this->assertSame('Bühne', $shiftPayload['craft']['name']);
        $this->assertSame('BÜ', $shiftPayload['craft']['abbreviation']);
    }

    #[Test]
    public function resource_worker_count_falls_back_to_loaded_relations_without_count_aggregates(): void
    {
        $room = Room::factory()->create();
        $event = $this->makeEvent($room, '2026-08-10 10:00:00', '2026-08-10 12:00:00');

        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
        ]);
        $qualification = ShiftQualification::factory()->create();
        $worker = User::factory()->create();
        $shift->users()->attach($worker->id, [
            'shift_qualification_id' => $qualification->id,
            'shift_count' => 1,
        ]);

        // Aufrufer ohne withCount (z. B. Bulk-Edit, Print-Layout) laden die Relationen voll —
        // die Resource muss dann auf die geladenen Collections zurückfallen
        $loaded = Event::query()
            ->with(['shifts.craft', 'shifts.users', 'shifts.freelancer', 'shifts.serviceProvider', 'shifts.shiftsQualifications'])
            ->findOrFail($event->id);

        $payload = MinimalCalendarEventResource::make($loaded)->resolve();

        $this->assertSame(1, $payload['shifts'][0]['worker_count']);
    }
}
