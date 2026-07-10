<?php

namespace Artwork\Modules\InventoryScheduling\Services;

use Artwork\Core\Database\Models\Model;
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

    public function deleteEventFromInventory(CraftInventoryItemEvent $craftInventoryItemEvent): bool
    {
        return $this->craftInventoryItemEventRepository->delete($craftInventoryItemEvent);
    }

    /**
     * Update the times of ALL inventory bookings of the given event —
     * an event can be booked on multiple items.
     */
    public function updateEventTimesInInventory(Event $event): void
    {
        foreach ($this->craftInventoryItemEventRepository->findAllByEventId($event->id) as $booking) {
            $this->updateEventTimeInInventory($booking, $event);
        }
    }

    /**
     * Remove ALL inventory bookings of the given event so no ghost bookings
     * keep reducing availability after the event is gone.
     */
    public function deleteAllEventsFromInventory(Event $event): void
    {
        foreach ($this->craftInventoryItemEventRepository->findAllByEventId($event->id) as $booking) {
            $this->deleteEventFromInventory($booking);
        }
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
                    'id' => $event->user?->id,
                    'name' => $event->user?->full_name,
                    'profile_photo_url' => $event->user?->profile_photo_url,
                ],
                'eventInfo' => [
                    'id' => $event->event?->id,
                    'name' => $event->event?->name ?? $event->event?->eventName,
                    'project_name' => $event->event?->project?->name,
                    'event_type' => $event->event?->event_type,
                ],
                'overbooked' => $overbooked[$event->id],
                'period' => $period,
            ];
        });
    }

    /**
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
        foreach ($events as $itemEvent) {
            $overbookedQuantities[$itemEvent->id] = 0;
        }

        // Expand multi-day bookings to every day they cover — grouping only by
        // start day would miss overlaps of bookings starting on different days.
        $eventsByDay = [];
        foreach ($events as $itemEvent) {
            $cursor = $itemEvent->start->copy()->startOfDay();
            $lastDay = $itemEvent->end->copy()->startOfDay();
            while ($cursor->lte($lastDay)) {
                $eventsByDay[$cursor->format('Y-m-d')][] = $itemEvent;
                $cursor->addDay();
            }
        }
        ksort($eventsByDay);

        foreach ($eventsByDay as $dayEvents) {
            $availableQuantity = $initialQuantity;

            foreach ($dayEvents as $itemEvent) {
                if (!$this->competesWithOtherBookings($itemEvent, $dayEvents)) {
                    // Even without competing bookings the quantity itself can exceed the stock.
                    if ($itemEvent->quantity > $initialQuantity) {
                        $overbookedQuantities[$itemEvent->id] = max(
                            $overbookedQuantities[$itemEvent->id],
                            $itemEvent->quantity - $initialQuantity
                        );
                    }
                    continue;
                }

                if ($availableQuantity >= $itemEvent->quantity) {
                    $availableQuantity -= $itemEvent->quantity;
                } else {
                    $missingQuantity = $itemEvent->quantity - $availableQuantity;
                    // A booking can be overbooked on several days — show the worst day.
                    $overbookedQuantities[$itemEvent->id] = max(
                        $overbookedQuantities[$itemEvent->id],
                        $missingQuantity
                    );
                    $availableQuantity = 0;
                }
            }
        }

        return $overbookedQuantities;
    }

    /**
     * Bookings that are separated in time on this day do not compete.
     *
     * @param array<int, CraftInventoryItemEvent> $dayEvents
     */
    private function competesWithOtherBookings(CraftInventoryItemEvent $itemEvent, array $dayEvents): bool
    {
        if ($itemEvent->is_all_day) {
            return true;
        }

        return collect($dayEvents)->first(
            fn (CraftInventoryItemEvent $otherEvent): bool =>
                $otherEvent->id !== $itemEvent->id &&
                (
                    $otherEvent->is_all_day ||
                    (
                        $otherEvent->start->lt($itemEvent->end) &&
                        $otherEvent->end->gt($itemEvent->start)
                    )
                )
        ) !== null;
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
        return \Illuminate\Support\Facades\DB::transaction(function () use ($events, $userId) {
            $lastEvent = null;
            foreach ($events as $event) {
                $eventObject = $this->eventService->findEventById($event['id']);
                if ($eventObject === null) {
                    // Soft-deleted in the meantime — skip instead of crashing mid-loop.
                    continue;
                }
                $items = $event['items'] ?? [];
                foreach ($items as $item) {
                    $count = (int) ($item['count'] ?? 0);
                    if ($count <= 0) {
                        // The modal sends every listed item; empty count means "do not book".
                        continue;
                    }
                    $itemEvent = $this->createNewCraftInventoryItem([
                        'craft_inventory_item_id' => $item['id'],
                        'event_id' => $eventObject->id,
                        'start' => $eventObject->start_time,
                        'end' => $eventObject->end_time,
                        'user_id' => $userId,
                        'quantity' => $count,
                    ]);
                    /** @var CraftInventoryItemEvent $lastEvent */
                    $lastEvent = $this->craftInventoryItemEventRepository->save($itemEvent);
                }
            }
            return $lastEvent;
        });
    }
}
