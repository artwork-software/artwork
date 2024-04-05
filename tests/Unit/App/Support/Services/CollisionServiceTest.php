<?php

namespace Tests\Unit\App\Support\Services;

use App\Support\Services\CollisionService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Http\Request;
use Tests\TestCase;

class CollisionServiceTest extends TestCase
{
    public function testGetCollision(): void
    {
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => now(),
            'end_time' => now()->addHour()
        ]);

        $request = new Request();
        $request->merge([
            'start' => now()->toDateTimeString(),
            'end' => now()->addHour()->toDateTimeString(),
            'roomId' => $room->id
        ]);

        $collisionService = new CollisionService();
        $collisions = $collisionService->getCollision($request, $event);

        $this->assertEquals(0, $collisions->count());
    }

    public function testGetCollisionCount(): void
    {
        $room = Room::factory()->create();
        Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => now(),
            'end_time' => now()->addHour()
        ]);

        $request = new Request();
        $request->merge([
            'start' => now()->toDateTimeString(),
            'end' => now()->addHour()->toDateTimeString(),
            'roomId' => $room->id
        ]);

        $collisionService = new CollisionService();
        $count = $collisionService->getCollisionCount($request);

        $this->assertEquals(1, $count);
    }

    public function testGetConflictEvents(): void
    {
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => now(),
            'end_time' => now()->addHour()
        ]);

        $request = new Request();
        $request->merge([
            'start' => now()->toDateTimeString(),
            'end' => now()->addHour()->toDateTimeString(),
            'roomId' => $room->id
        ]);

        $collisionService = new CollisionService();
        $conflictEvents = $collisionService->getConflictEvents($request);

        $this->assertNotEmpty($conflictEvents);
        $this->assertEquals($event->id, $conflictEvents[0]['id']);
    }

    public function testAdjoiningCollision(): void
    {
        $room = Room::factory()->create();
        $joiningRoom = Room::factory()->create();
        $room->adjoining_rooms()->attach($joiningRoom->id);

        Event::factory()->create([
            'room_id' => $joiningRoom->id,
            'start_time' => now(),
            'end_time' => now()->addHour()
        ]);

        $request = new Request();
        $request->merge([
            'start' => now()->toDateTimeString(),
            'end' => now()->addHour()->toDateTimeString(),
            'roomId' => $room->id
        ]);

        $collisionService = new CollisionService();
        $adjoiningCollisions = $collisionService->adjoiningCollision($request);

        $this->assertNotEmpty($adjoiningCollisions);
    }
}
