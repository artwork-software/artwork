<?php

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Collision\Service\CollisionService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;

test('find Collisions', function (Room $room, int $expectation): void {
    /** @var CollisionService $collisionService */
    $collisionService = app()->get(CollisionService::class);
    /** @var RoomService $roomService */
    $roomService = app()->get(RoomService::class);
    $roomService->getAllWithoutTrashed();
    $collisions = $collisionService->findCollisionCountForRoom($room, now(), now());
    expect($expectation)->toBe($collisions);
})->with([
    'no collisions' => [
        function (): Room {
            $room = Room::factory()->create();
            Event::factory(5)->create([
                'room_id' => $room->id,
                'start_time' => now()->addDay(),
                'end_time' => now()->addDay()
            ]);

            return $room;
        },
        0
    ],
    'one collisions' => [
        function (): Room {
            $room = Room::factory()->create();
            Event::factory()->create([
                'room_id' => $room->id,
                'start_time' => now(),
                'end_time' => now()
            ]);

            return $room;
        },
        1
    ],
    'two collisions' => [
        function (): Room {
            $room = Room::factory()->create();
            Event::factory(2)->create([
                'room_id' => $room->id,
                'start_time' => now(),
                'end_time' => now()
            ]);

            return $room;
        },
        2
    ],
]);
