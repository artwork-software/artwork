<?php

namespace Tests\Feature\EventController;

use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\RoomAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventIndexTest extends TestCase
{
    use RefreshDatabase;

    public function testEventIndex()
    {
        Event::factory()->create([
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithProjectId()
    {
        $project = Project::factory()->create();
        // with correct Project Id
        Event::factory()->create([
            'project_id' => $project->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);
        // with other project id
        Event::factory()->create([
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'projectId' => $project->id,
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithRoomId()
    {
        $room = Room::factory()->create();
        // expected
        Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);
        // unexpected
        Event::factory()->create([
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'roomId' => $room->id,
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithDates()
    {
        // expected
        Event::factory()->create([
            'start_time' => now()->subWeek(),
            'end_time' => now()->addHour()->subWeek(),
        ]);
        // unexpected
        Event::factory()->create([
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithRoomIds()
    {
        $room = Room::factory()->create();
        $room2 = Room::factory()->create();
        // expected
        Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);
        // unexpected
        Event::factory()->create([
            'room_id' => $room2->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'roomIds' => [$room->id],
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithAreaIds()
    {
        $room = Room::factory()->create(['area_id' => Area::factory()]);
        $room2 = Room::factory()->create(['area_id' => Area::factory()]);
        // expected
        Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);
        // unexpected
        Event::factory()->create([
            'room_id' => $room2->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'areaIds' => [$room->area->id],
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithEventTypeIds()
    {
        $eventType = EventType::factory()->create();
        $eventType2 = EventType::factory()->create();

        // expected
        Event::factory()->create([
            'event_type_id' => $eventType->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);
        // unexpected
        Event::factory()->create([
            'event_type_id' => $eventType2->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'eventTypeIds' => [$eventType->id],
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithRoomAttributeIds()
    {
        $room = Room::factory()->has(RoomAttribute::factory()->count(2), 'attributes')->create();
        $room2 = Room::factory()->has(RoomAttribute::factory()->count(1), 'attributes')->create();

        // expected
        Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);
        // unexpected
        Event::factory()->create([
            'room_id' => $room2->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'roomAttributeIds' => [$room->attributes->first()->id],
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithIsLoud()
    {
        // expected
        Event::factory()->create([
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'is_loud' => true,
        ]);
        // unexpected
        Event::factory()->create([
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'is_loud' => false,
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'isLoud' => true,
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'isLoud' => false,
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }

    public function testEventIndexWithHasAudience()
    {
        // expected
        Event::factory()->create([
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'audience' => true,
        ]);
        // unexpected
        Event::factory()->create([
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'audience' => false,
        ]);

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'hasAudience' => true,
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');

        $this->actingAs($this->adminUser())
            ->json('GET', route('events.index'), [
                'start' => now()->subDay(),
                'end' => now()->addDay(),
                'hasAudience' => false,
            ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'events');
    }
}
