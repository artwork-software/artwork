<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemEventRepository;

readonly class CraftInventoryItemEventServices
{
    public function __construct(
        private CraftInventoryItemEventRepository $craftInventoryItemEventRepository
    ) {
    }


    public function checkIfEventIsInInventoryPlaning(Event $event): ?CraftInventoryItemEvent
    {
        return $this->craftInventoryItemEventRepository->findEventByEventId($event->id);
    }

    public function updateEventTimeInInventory(
        CraftInventoryItemEvent $craftInventoryItemEvent,
        Event $event
    ): void {
        $this->craftInventoryItemEventRepository->update($craftInventoryItemEvent, [
            'start' => $event->start_time,
            'end' => $event->end_time,
            'is_all_day' => $event->allDay,
        ]);
    }
}
