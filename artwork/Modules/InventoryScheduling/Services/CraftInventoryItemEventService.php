<?php

namespace Artwork\Modules\InventoryScheduling\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryScheduling\Repositories\CraftInventoryItemEventRepository;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Collection;

class CraftInventoryItemEventService
{

    public function __construct(
        private readonly CraftInventoryItemEventRepository $craftInventoryItemEventRepository,
        private readonly EventService $eventService,
        private readonly SupportCarbon $supportCarbon
    ) {
    }


    public function checkIfEventIsInInventoryPlaning(int $id): ?CraftInventoryItemEvent
    {
        return $this->craftInventoryItemEventRepository->findEventByEventId($id);
    }

    public function updateEventTimeInInventory(
        CraftInventoryItemEvent $craftInventoryItemEvent,
        Event $event
    ): CraftInventoryItemEvent {
        /** @var CraftInventoryItemEvent $craftInventoryItemEvent */
        $craftInventoryItemEvent = $this->craftInventoryItemEventRepository->update(
            $craftInventoryItemEvent,
            [
                'start' => $event->getAttribute('start_time'),
                'end' => $event->getAttribute('end_time'),
                'is_all_day' => $event->getAttribute('allDay')
            ]
        );

        return $craftInventoryItemEvent;
    }

    public function createNewCraftInventoryItem(array $attributes = []): CraftInventoryItemEvent
    {
        return new CraftInventoryItemEvent($attributes);
    }

    public function dropItemToEvent(
        CraftInventoryItem $item,
        Event $event,
        int $userId,
        int $quantity
    ): CraftInventoryItemEvent {
        /** @todo use repo getNewModelInstance */
        $itemEvent = $this->createNewCraftInventoryItem([
            'craft_inventory_item_id' => $item->getAttribute('id'),
            'event_id' => $event->getAttribute('id'),
            'start' => $event->getAttribute('start_time'),
            'end' => $event->getAttribute('end_time'),
            'is_all_day' => $event->getAttribute('allDay'),
            'user_id' => $userId,
            'quantity' => $quantity,
        ]);

        /** @var CraftInventoryItemEvent $craftInventoryItemEvent */
        $craftInventoryItemEvent = $this->craftInventoryItemEventRepository->save($itemEvent);

        return $craftInventoryItemEvent;
    }

    public function updateQuantity(
        int $quantity,
        CraftInventoryItemEvent $craftInventoryItemEvent
    ): CraftInventoryItemEvent {
        /** @var CraftInventoryItemEvent $craftInventoryItemEvent */
        $craftInventoryItemEvent = $this->craftInventoryItemEventRepository->update(
            $craftInventoryItemEvent,
            [
                'quantity' => $quantity
            ]
        );

        return $craftInventoryItemEvent;
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
                    'event_id' => $eventObject->getAttribute('id'),
                    'start' => $eventObject->getAttribute('start_time'),
                    'end' => $eventObject->getAttribute('end_time'),
                    'user_id' => $userId,
                    'quantity' => $item['count'],
                ]);

                /** @var CraftInventoryItemEvent $lastEvent */
                $lastEvent = $this->craftInventoryItemEventRepository->save($itemEvent);
            }
        }

        return $lastEvent;
    }

    /**
     * @param CraftInventoryItem $item
     * @return array<string, mixed>
     */
    public function getItemEvents(CraftInventoryItem $item): array
    {
        $overbooked = $this->calculateOverbookedForAllEvents($item);

        return array_map(
            function (CraftInventoryItemEvent $event) use ($overbooked): array {
                $period = [];
                $start = $this->supportCarbon->parse($event->start);
                $end = $this->supportCarbon->parse($event->end);
                $diff = $start->diffInDays($end);
                for ($i = 0; $i <= $diff; $i++) {
                    $period[] = $start->copy()->addDays($i)->format('d.m.Y');
                }

                return [
                    'id' => $event->id,
                    'booking_id' => $event->id,
                    'quantity' => $event->quantity,
                    'comment' => $event->comment,
                    'date' => $this->supportCarbon->parse($event->start)->format('d.m.Y'),
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
            },
            $item->getAttribute('events')
        );
    }

    /**
     * @param CraftInventoryItem $item
     * @return array<int, int>
     */
    private function calculateOverbookedForAllEvents(CraftInventoryItem $item): array
    {
        $events = $item->events->sortBy(function (CraftInventoryItemEvent $itemEvent): Carbon {
            return $itemEvent->getAttribute('start');
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
}
