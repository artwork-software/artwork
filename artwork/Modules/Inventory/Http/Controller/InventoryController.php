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
use Artwork\Modules\InventoryManagement\Http\Requests\ItemEvent\DropItemOnInventoryRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemEventServices;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Auth\AuthManager;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly CraftService $craftService,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService,
        private readonly InventoryManagementUserFilterService $inventoryManagementUserFilterService,
        private readonly CalendarService $calendarService,
        private readonly CraftInventoryItemService $craftInventoryItemService,
        private readonly CraftInventoryItemEventServices $craftInventoryItemEventServices,
    ) {
    }

    public function inventory(): Response
    {
        return Inertia::render(
            'Inventory/InventoryManagement/Inventory',
            [
                'columns' => $this->craftsInventoryColumnService->getAllOrdered(),
                'crafts' => $this->craftService->getAllWithInventoryCategoriesRelations(),
                'craftFilters' => $this->inventoryManagementUserFilterService
                    ->getFilterOfUser($this->authManager->id())
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
            $this->authManager->user()?->calendar_filter
        );

        $crafts = $this->craftService->getCraftsWithInventory()->map(function ($craft) {
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
                                        'name' => $this->craftInventoryItemService->getItemName($item),
                                        'count' => $this->craftInventoryItemService->getItemCount($item),
                                        'events' => $this->craftInventoryItemEventServices->getItemEvents($item),
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];
        });

        return Inertia::render('Inventory/Scheduling', [
            'dateValue' => $showCalendar['dateValue'],
            'calendar' => $showCalendar['roomsWithEvents'],
            'days' => $showCalendar['days'],
            'crafts' => $crafts,
        ]);
    }

    public function dropItemToEvent(
        DropItemOnInventoryRequest $request,
        CraftInventoryItem $item,
        Event $event
    ): void {
        $this->craftInventoryItemEventServices->dropItemToEvent(
            $item,
            $event,
            $this->authManager->id(),
            $request->integer('quantity')
        );
    }
}
