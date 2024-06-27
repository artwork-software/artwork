<?php

namespace Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilterController;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private readonly CraftService $craftService,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService,
        private readonly CalendarService $calendarService,
        private readonly AuthManager $authManager,
    ) {
    }

    public function inventory(): Response
    {
        return Inertia::render(
            'Inventory/Inventory',
            [
                'columns' => $this->craftsInventoryColumnService->getAllOrdered(),
                'crafts' => $this->craftService->getAll(
                    [
                        'inventoryCategories',
                        'inventoryCategories.groups',
                        'inventoryCategories.groups.items',
                        'inventoryCategories.groups.items.cells',
                        'inventoryCategories.groups.items.cells.column',
                    ]
                )
            ]
        );
    }

    public function scheduling(
        ProjectService $projectService,
        RoomService $roomService,
        UserService $userService,
        FilterService $filterService,
        FilterController $filterController,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
    ): Response {
        [$startDate, $endDate] =
            $userService->getUserCalendarFilterDatesOrDefault($this->authManager->user()?->getCalendarFilter(),);

        $showCalendar = $this->createCalendarData(
            $startDate,
            $endDate,
            $userService,
            $filterService,
            $filterController,
            $roomService,
            $roomCategoryService,
            $roomAttributeService,
            $eventTypeService,
            $areaService,
            $projectService
        );

        $crafts = $this->getCrafts();

        return Inertia::render('Inventory/Scheduling', [
            'dateValue' => $showCalendar['dateValue'],
            'calendar' => $showCalendar['roomsWithEvents'],
            'days' => $showCalendar['days'],
            'crafts' => $crafts,
        ]);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $userService
     * @param $filterService
     * @param $filterController
     * @param $roomService
     * @param $roomCategoryService
     * @param $roomAttributeService
     * @param $eventTypeService
     * @param $areaService
     * @param $projectService
     * @return array<string, mixed>
     */
    private function createCalendarData(
        $startDate,
        $endDate,
        $userService,
        $filterService,
        $filterController,
        $roomService,
        $roomCategoryService,
        $roomAttributeService,
        $eventTypeService,
        $areaService,
        $projectService
    ): array {
        return $this->calendarService->createCalendarData(
            $startDate,
            $endDate,
            $userService,
            $filterService,
            $filterController,
            $roomService,
            $roomCategoryService,
            $roomAttributeService,
            $eventTypeService,
            $areaService,
            $projectService,
            $this->authManager->user()?->calendar_filter
        );
    }

    private function getCrafts(): Collection
    {
        return $this->craftService->getAll([
            'inventoryCategories',
            'inventoryCategories.groups',
            'inventoryCategories.groups.items',
            'inventoryCategories.groups.items.events',
            'inventoryCategories.groups.items.cells',
            'inventoryCategories.groups.items.cells.column',
        ])->map(function ($craft) {
            return [
                'id' => $craft->id,
                'name' => $craft->name,
                'inventory_categories' => $craft->inventoryCategories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'groups' => $category->groups->map(function ($group) {
                            return [
                                'id' => $group->id,
                                'name' => $group->name,
                                'items' => $group->items->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'name' => $this->getItemName($item),
                                        'count' => $this->getItemCount($item),
                                        'events' => $this->getItemEvents($item),
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];
        });
    }

    private function getItemName($item): string
    {
        return $item->cells->first(function ($cell) {
            return is_string($cell->cell_value);
        })->cell_value;
    }

    private function getItemCount($item): int
    {
        return $item->cells->first(function ($cell) {
            return is_numeric($cell->cell_value);
        })?->cell_value ?? 0;
    }

    /**
     * @param $item
     * @return Collection
     */
    private function getItemEvents($item): Collection
    {
        return $item->events->map(function ($event) use ($item) {
            return [
                'id' => $event->id,
                'booking_id' => $event->id,
                'quantity' => $event->quantity,
                'comment' => $event->comment,
                'date' => Carbon::parse($event->start)->format('d.m.Y'),
                'user' => [
                    'id' => $event->user->id,
                    'name' => $event->user->full_name,
                    'profile_photo_url' => $event->user->profile_photo_url,
                ],
                'eventInfo' => [
                    'id' => $event->event->id,
                    'name' => $event->event->eventName,
                    'project_name' => $event->event?->project?->name,
                    'event_type' => $event->event->event_type,
                ],
                'overbooked' => $this->calculateOverbookedForAllEvents($item)[$event->id],
            ];
        });
    }


    /**
     * Berechnet die Überbuchung eines CraftInventoryItems für
     * alle Events und gibt die fehlende Menge für jedes Event zurück.
     *
     * @param CraftInventoryItem $item
     * @return array<int, int>
     */
    private function calculateOverbookedForAllEvents(CraftInventoryItem $item): array
    {
        // Sortiere alle Events des Items nach Startzeit.
        $events = $item->events->sortBy(function ($itemEvent) {
            return $itemEvent->start;
        });

        // Initialisiere die verfügbare Menge des Items.
        $initialQuantity = $item->cells->first(function ($cell) {
            return is_numeric($cell->cell_value);
        })?->cell_value ?? 0;

        // Array, um die fehlende Menge für jedes Event zu speichern.
        $overbookedQuantities = [];

        // Gruppiere die Events nach ihrem Startdatum.
        $eventsByDay = $events->groupBy(function ($itemEvent) {
            return $itemEvent->start->format('Y-m-d');
        });

        // Berechne die benötigte Menge für jedes Event, pro Tag.
        foreach ($eventsByDay as $day => $dayEvents) {
            $availableQuantity = $initialQuantity;

            foreach ($dayEvents as $itemEvent) {
                if (
                    $itemEvent->is_all_day ||
                    $dayEvents->firstWhere(function ($otherEvent) use ($itemEvent) {
                        return $otherEvent->id !== $itemEvent->id &&
                            ($otherEvent->start->between($itemEvent->start, $itemEvent->end) ||
                                $otherEvent->end->between($itemEvent->start, $itemEvent->end) ||
                                ($itemEvent->start->between($otherEvent->start, $otherEvent->end) &&
                                    $itemEvent->end->between($otherEvent->start, $otherEvent->end)));
                    })
                ) {
                    if ($availableQuantity >= $itemEvent->quantity) {
                        // Reduziere die verfügbare Menge um die Menge des aktuellen Events.
                        $overbookedQuantities[$itemEvent->id] = 0;
                        $availableQuantity -= $itemEvent->quantity;
                    } else {
                        // Berechne die fehlende Menge.
                        $missingQuantity = $itemEvent->quantity - $availableQuantity;
                        $overbookedQuantities[$itemEvent->id] = $missingQuantity;
                        $availableQuantity = 0;
                    }
                } else {
                    $overbookedQuantities[$itemEvent->id] = 0;
                }
            }
        }

        return $overbookedQuantities;
    }


    public function dropItemToEvent(
        Request $request,
        CraftInventoryItem $item,
        Event $event
    ): void {
        $item->events()->create([
            'event_id' => $event->id,
            'quantity' => $request->integer('quantity'),
            'comment' => '',
            'start' => $event->start_time,
            'end' => $event->end_time,
            'is_all_day' => $event->allDay,
            'user_id' => $this->authManager->id(),
        ]);
    }
}
