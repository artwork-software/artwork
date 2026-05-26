<?php

namespace Tests\Unit\Modules\Timeline\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\Timeline\Services\TimelineService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TimelineServiceTest extends TestCase
{
    private TimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TimelineService::class);
    }

    #[Test]
    public function delete_soft_deletes_timeline(): void
    {
        $timeline = Timeline::factory()->create();

        $result = $this->service->delete($timeline);

        $this->assertTrue($result);
        $this->assertSoftDeleted('timelines', ['id' => $timeline->id]);
    }

    #[Test]
    public function force_delete_removes_timeline(): void
    {
        $timeline = Timeline::factory()->create();

        $this->service->forceDelete($timeline);

        $this->assertDatabaseMissing('timelines', ['id' => $timeline->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_timeline(): void
    {
        $timeline = Timeline::factory()->create();
        $timeline->delete();
        $this->assertSoftDeleted('timelines', ['id' => $timeline->id]);

        $this->service->restore($timeline);

        $this->assertDatabaseHas('timelines', ['id' => $timeline->id, 'deleted_at' => null]);
    }

    #[Test]
    public function delete_timelines_soft_deletes_each(): void
    {
        $timelines = Timeline::factory()->count(3)->create();

        $this->service->deleteTimelines($timelines);

        foreach ($timelines as $timeline) {
            $this->assertSoftDeleted('timelines', ['id' => $timeline->id]);
        }
    }

    #[Test]
    public function restore_timelines_restores_each(): void
    {
        $timelines = Timeline::factory()->count(2)->create();
        foreach ($timelines as $t) {
            $t->delete();
        }

        $this->service->restoreTimelines($timelines->fresh());

        foreach ($timelines as $timeline) {
            $this->assertDatabaseHas('timelines', ['id' => $timeline->id, 'deleted_at' => null]);
        }
    }

    #[Test]
    public function force_delete_timelines_hard_deletes_each(): void
    {
        $timelines = Timeline::factory()->count(2)->create();

        $this->service->forceDeleteTimelines($timelines);

        foreach ($timelines as $timeline) {
            $this->assertDatabaseMissing('timelines', ['id' => $timeline->id]);
        }
    }

    #[Test]
    public function update_timeline_persists_new_values(): void
    {
        $timeline = Timeline::factory()->create([
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-01',
            'start' => '08:00',
            'end' => '12:00',
            'description' => 'Old',
        ]);

        $data = collect([
            'start_date' => '2024-05-02',
            'end_date' => '2024-05-02',
            'start' => '10:00',
            'end' => '14:00',
            'description' => 'New description',
        ]);

        $result = $this->service->updateTimeline($timeline, $data);

        $this->assertSame('New description', $result->description);
        $this->assertSame('10:00', $result->start);
        $this->assertSame('14:00', $result->end);
        $this->assertDatabaseHas('timelines', [
            'id' => $timeline->id,
            'description' => 'New description',
            'start' => '10:00',
            'end' => '14:00',
        ]);
    }
}
