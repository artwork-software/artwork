<?php

namespace Artwork\Modules\Room\Collision\Service;

use App\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;

class CollisionService
{
    public function findCollisionCountForRoom(Room $room, Carbon $startDate, Carbon $endDate): int
    {
        return Event::query()
            ->whereOccursBetween($startDate, $endDate, true)
            ->where('room_id', $room->id)->count();
    }
}
