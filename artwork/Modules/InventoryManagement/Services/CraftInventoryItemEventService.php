<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemEventRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon as SupportCarbon;
use Carbon\Carbon;

class CraftInventoryItemEventService
{

    public function __construct(
        private readonly CraftInventoryItemEventRepository $craftInventoryItemEventRepository,
        private readonly EventService $eventService,
    ) {
    }


    public function checkIfEventIsInInventoryPlaning(Event $event): ?CraftInventoryItemEvent
    {
        return $this->craftInventoryItemEventRepository->findEventByEventId($event->id);
    }

    public function updateEventTimeInInventory(
        CraftInventoryItemEvent $craftInventoryItemEvent,
        Event $event
    ): Model|CraftInventoryItemEvent {
        return $this->craftInventoryItemEventRepository->update($craftInventoryItemEvent, [
            'start' => $event->start_time,
            'end' => $event->end_time,
            'is_all_day' => $event->allDay,
        ]);
    }

    /**
     * @param $item
     * @return Collection
     */
    public function getItemEvents($item): Collection
    {
        $overbooked = $this->calculateOverbookedForAllEvents($item);

        return $item->events->map(function (CraftInventoryItemEvent $event) use ($overbooked): array {
            $period = [];
            $start = SupportCarbon::parse($event->start);
            $end = SupportCarbon::parse($event->end);
            $diff = $start->diffInDays($end);
            for ($i = 0; $i <= $diff; $i++) {
                $period[] = $start->copy()->addDays($i)->format('d.m.Y');
            }

            return [
                'id' => $event->id,
                'booking_id' => $event->id,
                'quantity' => $event->quantity,
                'comment' => $event->comment,
                'date' => SupportCarbon::parse($event->start)->format('d.m.Y'),
                'user' => [
                    'id' => $event->user->id,
                    'name' => $event->user->full_name,
                    'profile_photo_url' => $event->user->profile_photo_url,
                ],
                'eventInfo' => [
                    'id' => $event->event->id,
                    'name' => $event->event->name ?? $event->event->eventName,
                    'project_name' => $event->event->project?->name,
                    'event_type' => $event->event->event_type,
                ],
                'overbooked' => $overbooked[$event->id],
                'period' => $period,
            ];
        });
    }

    /**
     * Berechnet die Überbuchung eines CraftInventoryItems
     * für alle Events und gibt die fehlende Menge für jedes Event zurück.
     *
     * @param CraftInventoryItem $item
     * @return array<int, int>
     */
    private function calculateOverbookedForAllEvents(CraftInventoryItem $item): array
    {
        $events = $item->events->sortBy(function (CraftInventoryItemEvent $itemEvent): Carbon {
            return $itemEvent->start;
        });

        $initialQuantity = $item->cells->first(function (CraftInventoryItemCell $cell): bool {
            return is_numeric($cell->cell_value);
        })?->cell_value ?? 0;

        $overbookedQuantities = [];

        $eventsByDay = $events->groupBy(function (CraftInventoryItemEvent $itemEvent): string {
            return $itemEvent->start->format('Y-m-d');
        });

        foreach ($eventsByDay as $day => $dayEvents) {
            $availableQuantity = $initialQuantity;

            foreach ($dayEvents as $itemEvent) {
                if (
                    $itemEvent->is_all_day ||
                    $dayEvents->firstWhere(function (CraftInventoryItemEvent $otherEvent) use ($itemEvent): bool {
                        return $otherEvent->id !== $itemEvent->id &&
                            ($otherEvent->start->between($itemEvent->start, $itemEvent->end) ||
                                $otherEvent->end->between($itemEvent->start, $itemEvent->end) ||
                                ($itemEvent->start->between($otherEvent->start, $otherEvent->end) &&
                                    $itemEvent->end->between($otherEvent->start, $otherEvent->end)));
                    })
                ) {
                    if ($availableQuantity >= $itemEvent->quantity) {
                        $overbookedQuantities[$itemEvent->id] = 0;
                        $availableQuantity -= $itemEvent->quantity;
                    } else {
                        $missingQuantity = $itemEvent->quantity - $availableQuantity;
                        $overbookedQuantities[$itemEvent->id] = $missingQuantity;
                        $availableQuantity = 0;
                    }
                } else {
                    $overbookedQuantities[$itemEvent->id] = 0;
                }
            }
        }

        foreach ($eventsByDay as $day => $dayEvents) {
            $availableQuantity = $initialQuantity;

            foreach ($dayEvents as $itemEvent) {
                if ($availableQuantity >= $itemEvent->quantity) {
                    $overbookedQuantities[$itemEvent->id] = 0;
                    $availableQuantity -= $itemEvent->quantity;
                } else {
                    $missingQuantity = $itemEvent->quantity - $availableQuantity;
                    $overbookedQuantities[$itemEvent->id] = $missingQuantity;
                    $availableQuantity = 0;
                }
            }
        }

        return $overbookedQuantities;
    }


    public function dropItemToEvent(
        CraftInventoryItem $item,
        Event $event,
        int $userId,
        int $quantity
    ): Model|CraftInventoryItemEvent {
        $itemEvent = $this->createNewCraftInventoryItem([
            'craft_inventory_item_id' => $item->id,
            'event_id' => $event->id,
            'start' => $event->start_time,
            'end' => $event->end_time,
            'is_all_day' => $event->allDay,
            'user_id' => $userId,
            'quantity' => $quantity,
        ]);
        return $this->craftInventoryItemEventRepository->save($itemEvent);
    }

    public function updateQuantity(
        int $quantity,
        CraftInventoryItemEvent $craftInventoryItemEvent
    ): Model|CraftInventoryItemEvent {
        return $this->craftInventoryItemEventRepository->update($craftInventoryItemEvent, ['quantity' => $quantity]);
    }

    public function createNewCraftInventoryItem(array $attributes = []): CraftInventoryItemEvent
    {
        return new CraftInventoryItemEvent($attributes);
    }

    public function storeMultiple(Collection $events, int $userId): CraftInventoryItemEvent|null
    {
        $lastEvent = null;
        foreach ($events as $event) {
            $eventObject = $this->eventService->findEventById($event['id']);
            $items = $event['items'] ?? [];
            foreach ($items as $item) {
                $itemEvent = $this->createNewCraftInventoryItem([
                    'craft_inventory_item_id' => $item['id'],
                    'event_id' => $eventObject->id,
                    'start' => $eventObject->start_time,
                    'end' => $eventObject->end_time,
                    'user_id' => $userId,
                    'quantity' => $item['count'],
                ]);
                /** @var CraftInventoryItemEvent $lastEvent */
                $lastEvent = $this->craftInventoryItemEventRepository->save($itemEvent);
            }
        }
        return $lastEvent;
    }
}
