<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilterController;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Artwork\Modules\InventoryScheduling\Http\Requests\DropItemOnInventoryRequest;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Auth\AuthManager;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Throwable;

class InventoryController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly CraftService $craftService,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService,
        private readonly InventoryManagementUserFilterService $inventoryManagementUserFilterService,
        private readonly CraftInventoryItemEventService $craftInventoryItemEventService,
        private readonly ResponseFactory $responseFactory,
        private readonly CalendarDataService $calendarDataService
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

    /**
     * @throws Throwable
     */
    public function scheduling(
        UserService $userService,
    ): Response {
        $user = $userService->getAuthUser();


        [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault(
            $user?->calendar_filter
        );

        $showCalendar = $this->calendarDataService->createCalendarData(
            $startDate,
            $endDate,
            $userService->getAuthUser()->getAttribute('calendar_filter'),
            null,
            null,
            true
        );

        $crafts = $this->craftService->getCraftsWithInventory($startDate, $endDate);

        return Inertia::render('Inventory/Scheduling', [
            'dateValue' => $showCalendar['dateValue'],
            'calendar' => $showCalendar['roomsWithEvents'],
            'days' => $showCalendar['days'],
            'crafts' => $crafts,
            'craftFilters' => $this->inventoryManagementUserFilterService
                ->getFilterOfUser($this->authManager->id())
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
