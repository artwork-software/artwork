<?php

namespace Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilterController;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private readonly CraftService $craftService,
        private readonly CalendarService $calendarService,
        private readonly AuthManager $authManager,
    )
    {
    }

    public function inventory(): Response
    {
        return Inertia::render(
            'Inventory/Inventory',
            [
                'columns' => [
                    [
                        'id' => 1,
                        'type' => 'text',
                        'name' => 'Name'
                    ],
                    [
                        'id' => 2,
                        'type' => 'number',
                        'name' => 'Anzahl'
                    ],
                    [
                        'id' => 3,
                        'type' => 'textarea',
                        'name' => 'Kommentar'
                    ],
                    [
                        'id' => 4,
                        'type' => 'date',
                        'name' => 'Datum'
                    ],
                    [
                        'id' => 5,
                        'type' => 'checkbox',
                        'name' => 'Kürzlich aufbereitet'
                    ],
                    [
                        'id' => 6,
                        'type' => 'select',
                        'name' => 'Maximale Dispositionsdauer',
                        'options' => [
                            [
                                '1 Tag',
                                '3 Tage',
                                '1 Woche',
                                '2 Wochen',
                                '1 Monat'
                            ]
                        ]
                    ]
                ],
                'crafts' => $this->craftService->getAll()
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
    ): Response
    {

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
        return Inertia::render(
            'Inventory/Scheduling',
            [
                'dateValue' => $showCalendar['dateValue'],
                'calendar' => $showCalendar['roomsWithEvents'],
                'days' => $showCalendar['days'],
                'crafts' => $this->craftService->getAll()
            ]
        );
    }
}
