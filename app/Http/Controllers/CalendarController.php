<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;

class CalendarController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getCalendarFilterDefinitions(
        FilterService $filterService,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
        ProjectService $projectService,
        RoomService $roomService
    ): array {
        return $filterService->getCalendarFilterDefinitions(
            $roomCategoryService,
            $roomAttributeService,
            $eventTypeService,
            $areaService,
            $projectService,
            $roomService
        );
    }
}
