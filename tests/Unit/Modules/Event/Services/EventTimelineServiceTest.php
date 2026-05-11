<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventTimelineService;
use Artwork\Modules\Timeline\Models\Timeline;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventTimelineServiceTest extends TestCase
{
    private EventTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventTimelineService::class);
    }

    #[Test]
    public function update_time_lines_replaces_existing_timelines(): void
    {
        $event = Event::factory()->create([
            'start_time' => '2026-06-01 09:00:00',
            'end_time' => '2026-06-01 17:00:00',
        ]);
        Timeline::create([
            'event_id' => $event->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'start' => '08:00:00',
            'end' => '09:00:00',
            'description' => 'Old',
        ]);

        $this->service->updateTimeLines($event, [
            ['start' => '10:00', 'end' => '11:00', 'description' => 'New 1'],
            ['start' => '12:00', 'end' => '13:00', 'description' => 'New 2'],
        ]);

        $timelines = Timeline::where('event_id', $event->id)->get();
        $this->assertCount(2, $timelines);
        $descriptions = $timelines->pluck('description')->all();
        $this->assertContains('New 1', $descriptions);
        $this->assertContains('New 2', $descriptions);
        $this->assertNotContains('Old', $descriptions);
    }

    #[Test]
    public function update_time_lines_with_empty_dataset_clears_timelines(): void
    {
        $event = Event::factory()->create([
            'start_time' => '2026-06-01 09:00:00',
            'end_time' => '2026-06-01 17:00:00',
        ]);
        Timeline::create([
            'event_id' => $event->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'start' => '08:00:00',
            'end' => '09:00:00',
            'description' => 'Existing',
        ]);

        $this->service->updateTimeLines($event, []);

        $this->assertSame(0, Timeline::where('event_id', $event->id)->count());
    }

    #[Test]
    public function add_time_lines_appends_without_clearing_existing(): void
    {
        $event = Event::factory()->create([
            'start_time' => '2026-06-01 09:00:00',
            'end_time' => '2026-06-01 17:00:00',
        ]);
        Timeline::create([
            'event_id' => $event->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'start' => '08:00:00',
            'end' => '09:00:00',
            'description' => 'Existing',
        ]);

        $this->service->addTimeLines($event, [
            ['start' => '14:00', 'end' => '15:00', 'description' => 'Added'],
        ]);

        $this->assertSame(2, Timeline::where('event_id', $event->id)->count());
    }

    #[Test]
    public function add_time_lines_with_empty_array_does_nothing(): void
    {
        $event = Event::factory()->create([
            'start_time' => '2026-06-01 09:00:00',
            'end_time' => '2026-06-01 17:00:00',
        ]);

        $this->service->addTimeLines($event, []);

        $this->assertSame(0, Timeline::where('event_id', $event->id)->count());
    }
}
