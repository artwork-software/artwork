<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemEvent;

class CraftInventoryItemEventRepository extends BaseRepository
{
    public function findEventByEventId(int $eventId): ?CraftInventoryItemEvent
    {
        return CraftInventoryItemEvent::where('event_id', $eventId)->first();
    }
}
