<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\Services\EventPropertyService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\User\Services\UserService;

class ProjectTabCalendarService
{
    public function __construct(
        private readonly EventService $eventService,
        private readonly CalendarService $calendarService,
        private readonly RoomService $roomService,
        private readonly UserService $userService,
        private readonly FilterService $filterService,
        private readonly ProjectTabService $projectTabService,
        private readonly EventTypeService $eventTypeService,
        private readonly AreaService $areaService,
        private readonly ProjectService $projectService,
        private readonly ProjectCreateSettings $projectCreateSettings,
        private readonly EventPropertyService $eventPropertyService,
    ) {
    }

    public function buildCalendarPayload(Project $project, bool $atAGlance = false): array
    {
        $dto = $atAGlance
            ? $this->eventService->createEventManagementDtoForAtAGlance(
                $this->calendarService,
                $this->roomService,
                $this->userService,
                $this->filterService,
                $this->projectTabService,
                $this->eventTypeService,
                $this->areaService,
                $this->projectService,
                $this->projectCreateSettings,
                $this->eventPropertyService,
                $project
            )
            : $this->eventService->createEventManagementDto(
                $this->roomService,
                $this->userService,
                $this->filterService,
                $this->projectTabService,
                $this->eventTypeService,
                $this->areaService,
                $this->projectService,
                $this->projectCreateSettings,
                $this->eventPropertyService,
                $project
            );

        return [
            'CalendarTab' => $dto->toArray(),
        ];
    }
}

