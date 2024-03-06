<?php

namespace Artwork\Modules\Room\Collision\Service;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;

class CollisionService
{
    public function findCollisionCountForRoom(
        Room $room,
        Carbon $startDate,
        Carbon $endDate,
        int|null $currentEventId = null
    ): int {
        $query = Event::query()
            ->startAndEndTimeOverlap($startDate, $endDate)
            ->where('room_id', $room->id);

        if ($currentEventId) {
            $query->isNotId($currentEventId);
        }

        return $query->count();
    }
}
