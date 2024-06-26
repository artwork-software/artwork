<?php

namespace Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilterController;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        Request $request,
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

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $showCalendar = $this->calendarService->createCalendarData(
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
            $this->authManager->user()?->calendar_filter,
        );

        $crafts = $this->craftService->getAll([
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
                                        // name get the first column in items->cells where are a string
                                        'name' => $item->cells->first(function ($cell) {
                                            return is_string($cell->cell_value);
                                        })->cell_value,
                                        // count get the first column in items->cells where are a number
                                        'count' => $item->cells->first(function ($cell2) {
                                            return is_numeric($cell2->cell_value);
                                        })?->cell_value ?? 0,
                                        'events' => $item->events->map(function ($event) use ($item) {
                                            return [
                                                'id' => $event->id,
                                                'booking_id' => $event->id,
                                                'quantity' => $event->quantity,
                                                'comment' => $event->comment,
                                                'date' => Carbon::parse($event->date)->format('d.m.Y'),
                                                'user' => [
                                                    'id' => $event->user->id,
                                                    'name' => $event->user->full_name,
                                                    'profile_photo_url' => $event->user->profile_photo_url,
                                                ],
                                                'eventInfo' => [
                                                    'id' => $event->event->id,
                                                    'name' => $event->event->eventName,
                                                    'project_name' => $event->event?->project?->name,
                                                ],
                                            ];
                                        }),
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];
        });

        return Inertia::render(
            'Inventory/Scheduling',
            [
                'dateValue' => $showCalendar['dateValue'],
                'calendar' => $showCalendar['roomsWithEvents'],
                'days' => $showCalendar['days'],
                'crafts' => $crafts,
            ]
        );
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
            'date' => Carbon::parse($request->string('date'))->format('Y-m-d'),
            'user_id' => $this->authManager->id(),
        ]);
    }
}
