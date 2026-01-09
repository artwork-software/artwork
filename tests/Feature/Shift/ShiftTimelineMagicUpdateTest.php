<?php

namespace Tests\Feature\Shift;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Timeline\Models\Timeline;
use Tests\TestCase;

class ShiftTimelineMagicUpdateTest extends TestCase
{
    public function testMagicTimelineUpdateReplacesExistingTimelines(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $event = Event::factory()->create();

        $oldTimeline = Timeline::factory()->create([
            'event_id' => $event->id,
        ]);

        $payload = [
            'dataset' => [
                ['start' => '08:00', 'end' => '09:00', 'description' => 'Aufbau'],
                ['start' => '09:00', 'end' => '10:00', 'description' => 'Soundcheck'],
            ],
        ];

        $response = $this->post(route('edit.timeline.event', ['event' => $event->id]), $payload);
        $response->assertSuccessful();

        $this->assertSoftDeleted('timelines', ['id' => $oldTimeline->id]);

        $this->assertSame(2, Timeline::query()->where('event_id', $event->id)->count());
        $this->assertDatabaseHas('timelines', [
            'event_id' => $event->id,
            'start' => '08:00:00',
            'end' => '09:00:00',
            'description' => 'Aufbau',
        ]);
        $this->assertDatabaseHas('timelines', [
            'event_id' => $event->id,
            'start' => '09:00:00',
            'end' => '10:00:00',
            'description' => 'Soundcheck',
        ]);
    }
}
