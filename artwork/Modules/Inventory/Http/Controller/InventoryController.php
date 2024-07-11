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
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Artwork\Modules\InventoryScheduling\Http\Requests\DropItemOnInventoryRequest;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Auth\AuthManager;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;

class InventoryController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly CraftService $craftService,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService,
        private readonly InventoryManagementUserFilterService $inventoryManagementUserFilterService,
        private readonly CalendarService $calendarService,
        private readonly CraftInventoryItemEventService $craftInventoryItemEventService,
        private readonly ResponseFactory $responseFactory
    ) {
    }

    public function inventory(): Response
    {
        return $this->responseFactory->render(
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
        [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault($this->authManager->user());

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
            $this->authManager->user()->calendar_filter
        );

        $crafts = $this->craftService->getCraftsWithInventory();

        return Inertia::render('Inventory/Scheduling', [
            'dateValue' => $showCalendar['dateValue'],
            'calendar' => $showCalendar['roomsWithEvents'],
            'days' => $showCalendar['days'],
            'crafts' => $crafts
        ]);
    }

    public function dropItemToEvent(
        DropItemOnInventoryRequest $request,
        CraftInventoryItem $item,
        Event $event
    ): void {
        $this->craftInventoryItemEventService->dropItemToEvent(
            $item,
            $event,
            $this->authManager->id(),
            $request->integer('quantity')
        );
    }
}
