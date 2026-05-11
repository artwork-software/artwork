<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Services\EventService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventServiceTest extends TestCase
{
    private EventService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventService::class);
    }

    #[Test]
    public function get_all_returns_all_events(): void
    {
        Event::factory()->count(2)->create();

        $events = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertCount(2, $events);
    }

    #[Test]
    public function find_event_by_id_returns_event(): void
    {
        $event = Event::factory()->create();

        $found = $this->service->findEventById($event->id);

        $this->assertInstanceOf(Event::class, $found);
        $this->assertSame($event->id, $found->id);
    }

    #[Test]
    public function find_event_by_id_returns_null_for_unknown(): void
    {
        $this->assertNull($this->service->findEventById(99999));
    }

    #[Test]
    public function get_earliest_start_time_returns_event_start_when_no_timelines_or_shifts(): void
    {
        $event = Event::factory()->create([
            'start_time' => '2026-06-01 12:00:00',
            'end_time' => '2026-06-01 14:00:00',
        ]);

        $earliest = $this->service->getEarliestStartTime($event->fresh());

        $this->assertTrue(Carbon::parse('2026-06-01 12:00:00')->equalTo($earliest));
    }

    #[Test]
    public function get_latest_end_time_returns_event_end_when_no_timelines_or_shifts(): void
    {
        $event = Event::factory()->create([
            'start_time' => '2026-06-01 12:00:00',
            'end_time' => '2026-06-01 14:00:00',
        ]);

        $latest = $this->service->getLatestEndTime($event->fresh());

        $this->assertTrue(Carbon::parse('2026-06-01 14:00:00')->equalTo($latest));
    }

    #[Test]
    public function process_event_times_returns_carbon_pair_for_valid_times(): void
    {
        $day = Carbon::parse('2026-06-01');
        [$start, $end, $allDay] = $this->service->processEventTimes($day, '08:00', '12:00');

        $this->assertInstanceOf(Carbon::class, $start);
        $this->assertInstanceOf(Carbon::class, $end);
        $this->assertFalse($allDay);
        $this->assertSame('2026-06-01 08:00:00', $start->toDateTimeString());
        $this->assertSame('2026-06-01 12:00:00', $end->toDateTimeString());
    }

    #[Test]
    public function process_event_times_returns_all_day_when_times_empty(): void
    {
        $day = Carbon::parse('2026-06-01');
        [$start, $end, $allDay] = $this->service->processEventTimes($day, null, null);

        $this->assertTrue($allDay);
        $this->assertSame('2026-06-01 00:00:00', $start->toDateTimeString());
        $this->assertSame('2026-06-01 23:59:59', $end->toDateTimeString());
    }

    #[Test]
    public function process_event_times_rolls_to_next_day_when_end_before_start(): void
    {
        $day = Carbon::parse('2026-06-01');
        [$start, $end, $allDay] = $this->service->processEventTimes($day, '22:00', '02:00');

        $this->assertFalse($allDay);
        $this->assertSame('2026-06-01 22:00:00', $start->toDateTimeString());
        $this->assertSame('2026-06-02 02:00:00', $end->toDateTimeString());
    }

    #[Test]
    public function process_event_times_for_timeline_handles_both_null(): void
    {
        $day = Carbon::parse('2026-06-01');
        [$start, $end] = $this->service->processEventTimesForTimeline($day, null, null);

        $this->assertSame('2026-06-01 00:00:00', $start->toDateTimeString());
        $this->assertSame('2026-06-01 23:59:59', $end->toDateTimeString());
    }

    #[Test]
    public function process_event_times_for_timeline_uses_start_when_end_missing(): void
    {
        $day = Carbon::parse('2026-06-01');
        [$start, $end] = $this->service->processEventTimesForTimeline($day, '10:00', null);

        $this->assertSame('2026-06-01 10:00:00', $start->toDateTimeString());
        $this->assertSame('2026-06-01 10:00:00', $end->toDateTimeString());
    }

    #[Test]
    public function update_modifies_event_attributes(): void
    {
        $event = Event::factory()->create(['eventName' => 'Old']);

        $updated = $this->service->update($event, ['eventName' => 'New']);

        $this->assertSame('New', $updated->fresh()->eventName);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'eventName' => 'New']);
    }

    #[Test]
    public function attach_event_property_attaches_property(): void
    {
        $event = Event::factory()->create();
        $property = EventProperty::factory()->create();

        $this->service->attachEventProperty($event, $property);

        $this->assertTrue(
            $event->eventProperties()->where('event_properties.id', $property->id)->exists()
        );
    }

    #[Test]
    public function bulk_delete_event_returns_early_for_empty_collection(): void
    {
        // No exception should be thrown
        $this->service->bulkDeleteEvent(new SupportCollection());

        $this->assertTrue(true);
    }

    #[Test]
    public function bulk_delete_event_soft_deletes_events(): void
    {
        $events = Event::factory()->count(2)->create();

        $this->service->bulkDeleteEvent(new SupportCollection($events->pluck('id')->all()));

        foreach ($events as $event) {
            $this->assertSoftDeleted('events', ['id' => $event->id]);
        }
    }

    #[Test]
    public function get_events_without_room_returns_only_roomless_events(): void
    {
        $withRoom = Event::factory()->create();
        Event::where('id', $withRoom->id)->update(['room_id' => $withRoom->room_id]);

        $withoutRoom = Event::factory()->create();
        Event::where('id', $withoutRoom->id)->update(['room_id' => null]);

        $events = $this->service->getEventsWithoutRoom();

        $this->assertTrue($events->contains('id', $withoutRoom->id));
        $this->assertFalse($events->contains('id', $withRoom->id));
    }
}
