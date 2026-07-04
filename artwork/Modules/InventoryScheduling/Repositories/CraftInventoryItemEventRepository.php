<?php

namespace Artwork\Modules\InventoryScheduling\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;

class CraftInventoryItemEventRepository extends BaseRepository
{
    public function findEventByEventId(int $eventId): ?CraftInventoryItemEvent
    {
        return CraftInventoryItemEvent::where('event_id', $eventId)->first();
    }

    /**
     * An event can be booked on any number of inventory items — mutations
     * must always handle ALL bookings, not just the first one.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, CraftInventoryItemEvent>
     */
    public function findAllByEventId(int $eventId): \Illuminate\Database\Eloquent\Collection
    {
        return CraftInventoryItemEvent::where('event_id', $eventId)->get();
    }
}
