<?php

namespace Artwork\Modules\Room\Collision\Service;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;

class CollisionService
{
    /**
     * Überschneidende Termine je Raum mit einer gruppierten Abfrage. Der Termin-Dialog fragt das bei jeder
     * Datums-/Zeitänderung ab; vorher lief je Raum eine eigene COUNT-Abfrage.
     *
     * @param array<int, int> $roomIds
     * @return array<int, int> Raum-ID => Anzahl, Räume ohne Überschneidung mit 0
     */
    public function findCollisionCountsForRooms(
        array $roomIds,
        Carbon $startDate,
        Carbon $endDate,
        int|null $currentEventId = null
    ): array {
        $query = Event::query()
            ->startAndEndTimeOverlap($startDate, $endDate)
            ->whereIn('room_id', $roomIds);

        if ($currentEventId) {
            $query->isNotId($currentEventId);
        }

        $countsByRoom = $query->toBase()
            ->selectRaw('room_id, COUNT(*) as aggregate')
            ->groupBy('room_id')
            ->pluck('aggregate', 'room_id');

        $collisions = [];
        foreach ($roomIds as $roomId) {
            $collisions[$roomId] = (int) ($countsByRoom[$roomId] ?? 0);
        }

        return $collisions;
    }
}
