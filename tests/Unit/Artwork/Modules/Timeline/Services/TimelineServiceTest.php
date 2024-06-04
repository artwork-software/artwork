<?php

namespace Tests\Unit\Artwork\Modules\Timeline\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\Timeline\Repositories\TimelineRepository;
use Artwork\Modules\ShiftPresetTimeline\Models\ShiftPresetTimeline;
use Artwork\Modules\Timeline\Models\Timeline;
use Tests\TestCase;

class TimelineServiceTest extends TestCase
{
    private TimelineService $timelineService;
    protected function setUp(): void
    {
        parent::setUp();
        $this->timelineService = $this->app->make(TimelineService::class);
    }

    public function testCreateFromShiftPresetTimeline(): void
    {
        $shiftPresetTimeline = ShiftPresetTimeline::factory()->make();
        $event = Event::factory()->create();

        $timeline = $this->timelineService->createFromShiftPresetTimeline($shiftPresetTimeline, $event);

        $this->assertInstanceOf(Timeline::class, $timeline);
        $this->assertEquals($event->id, $timeline->event_id);
        $this->assertEquals($shiftPresetTimeline->start, $timeline->start);
        $this->assertEquals($shiftPresetTimeline->end, $timeline->end);
        $this->assertEquals($shiftPresetTimeline->description, $timeline->description);
    }

    public function testDelete(): void
    {
        $timeline = Timeline::factory()->create();

        $this->timelineService->delete($timeline);

        $this->assertSoftDeleted('timelines', ['id' => $timeline->id]);
    }

    public function testDeleteTimelines(): void
    {
        $timelines = Timeline::factory()->count(3)->create();

        $this->timelineService->deleteTimelines($timelines);

        foreach ($timelines as $timeline) {
            $this->assertSoftDeleted('timelines', ['id' => $timeline->id]);
        }
    }

    public function testRestoreTimelines(): void
    {
        $timelines = Timeline::factory()->count(3)->create();
        foreach ($timelines as $timeline) {
            $timeline->delete();
        }

        $this->timelineService->restoreTimelines($timelines);

        foreach ($timelines as $timeline) {
            $this->assertDatabaseHas('timelines', ['id' => $timeline->id]);
        }
    }

    public function testForceDelete(): void
    {
        $timeline = Timeline::factory()->create();

        $this->timelineService->forceDelete($timeline);

        $this->assertDatabaseMissing('timelines', ['id' => $timeline->id]);
    }

    public function testForceDeleteTimelines(): void
    {
        $timelines = Timeline::factory()->count(3)->create();

        $this->timelineService->forceDeleteTimelines($timelines);

        foreach ($timelines as $timeline) {
            $this->assertDatabaseMissing('timelines', ['id' => $timeline->id]);
        }
    }

    public function testRestore(): void
    {
        $timeline = Timeline::factory()->create();
        $timeline->delete();

        $this->timelineService->restore($timeline);

        $this->assertDatabaseHas('timelines', ['id' => $timeline->id]);
    }
}
