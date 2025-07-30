<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Category\Http\Resources\CategoryIndexResource;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Services\EventPropertyService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Http\Resources\ProjectIndexAdminResource;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\DTOs\ShowDto;
use Artwork\Modules\Room\Http\Resources\AdjoiningRoomIndexResource;
use Artwork\Modules\Room\Http\Resources\AttributeIndexResource;
use Artwork\Modules\Room\Http\Resources\RoomCalendarResource;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\Room\Repositories\RoomAttributeRepository;
use Artwork\Modules\Room\Repositories\RoomCategoryRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;

readonly class RoomFrontendModelService
{

    public function __construct(
        private CalendarDataService $calendarDataService,
        private ProjectTabService $projectTabService,
        private RoomCategoryRepository $roomCategoryRepository,
        private RoomAttributeRepository $roomAttributeRepository,
        private EventTypeService $eventTypeService,
        private RoomRepository $roomRepository,
        private UserService $userService,
        private ProjectService $projectService,
        private EventPropertyService $eventPropertyService,
    ) {
    }
    public function createShowDto(
        Room $room,
        User $user
    ): ShowDto {
        [$startDate, $endDate] = $this->userService->getUserCalendarFilterDatesOrDefault(
            $user->userFilters()->calendarFilter()->first()
        );

        $calendarData = $this->calendarDataService->createCalendarData(
            startDate: $startDate,
            endDate: $endDate,
            calendarFilter: $user->userFilters()->calendarFilter()->first(),
            room: $room
        );

        return ShowDto::newInstance()
            ->setEventStatuses(EventStatus::orderBy('order')->get())
            ->setCalendar($calendarData['roomsWithEvents'])
            ->setDays($calendarData['days'])
            ->setEventsWithoutRoom($calendarData['eventsWithoutRoom'])
            ->setFilterOptions($calendarData['filterOptions'])
            ->setDateValue($calendarData['dateValue'])
            ->setPersonalFilters($calendarData['personalFilters'])
            ->setCalendarType($calendarData['calendarType'])
            ->setSelectedDate($calendarData['selectedDate'])
            ->setUserFilters($calendarData['user_filters'])
            ->setFirstProjectTabId($this->projectTabService->getFirstProjectTabId())
            ->setFirstProjectCalendarTabId(
                $this->projectTabService
                    ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR)
            )
            ->setAvailableCategories($this->roomCategoryRepository->getAll())
            ->setAvailableAttributes($this->roomAttributeRepository->getAll())
            ->setIsRoomAdmin($this->roomRepository->getUserWhereIsAdmin($room, $user->id)->count() > 0)
            ->setAvailableRooms($this->roomRepository->getRoomsNotIdIn([$room->id]))
            ->setRoomCategoryIds($this->roomRepository->getRoomCategoryIds($room))
            ->setRoomAttributeIds($this->roomRepository->getRoomAttributeIds($room))
            ->setAdjoiningRoomIds($this->roomRepository->getAdjoiningRoomIds($room))
            ->setRoom(RoomCalendarResource::make($room))
            ->setRoomCategories(CategoryIndexResource::collection($room->categories)->resolve())
            ->setRoomAttributes(AttributeIndexResource::collection($room->attributes)->resolve())
            ->setAdjoiningRooms(AdjoiningRoomIndexResource::collection($room->adjoining_rooms)->resolve())
            ->setRooms(RoomIndexWithoutEventsResource::collection($this
                ->roomRepository->allWithoutTrashed())->resolve())
            ->setEventTypes(EventTypeResource::collection($this->eventTypeService->getAll())->resolve())
            ->setProjects(
                ProjectIndexAdminResource::collection($this->projectService
                    ->getProjectsWithAccessBudgetAndManagerUsers())
                    ->resolve()
            )
            ->setEventProperties($this->eventPropertyService->getAll());
    }
}
