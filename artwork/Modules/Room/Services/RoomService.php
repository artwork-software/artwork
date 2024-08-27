<?php

namespace Artwork\Modules\Room\Services;

use App\Http\Controllers\FilterController;
use App\Http\Resources\MinimalShiftPlanEventResource;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Category\Http\Resources\CategoryIndexResource;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Http\Resources\MinimalInventorySchedulingEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Http\Resources\ProjectIndexAdminResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\DTOs\ShowDto;
use Artwork\Modules\Room\Http\Resources\AdjoiningRoomIndexResource;
use Artwork\Modules\Room\Http\Resources\AttributeIndexResource;
use Artwork\Modules\Room\Http\Resources\RoomCalendarResource;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\FiltersRoomsBy;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Artwork\Modules\UserShiftCalendarFilter\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Throwable;

class RoomService
{
    use FiltersRoomsBy;

    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly CalendarDataService $calendarDataService
    ) {
    }

    public function save(Room $room): Room
    {
        /** @var Room $room */
        $room = $this->roomRepository->save($room);

        return $room;
    }

    public function delete(Room $room): bool
    {
        return $this->roomRepository->delete($room);
    }

    public function duplicateByRoomModel(Room $room): Room
    {
        $new_room = $this->duplicateByRoomModelWithoutArea($room);
        $room->area->rooms()->save($new_room);

        return $new_room;
    }

    public function duplicateByRoomModelWithoutArea(Room $room): Room
    {
        $new_room = $room->replicate();
        $new_room->name = '(Kopie) ' . $room->name;
        $this->roomRepository->save($new_room);

        return $new_room;
    }

    public function deleteAllByArea(Area $area): void
    {
        $this->roomRepository->deleteByReference($area, 'rooms');
    }

    public function getAllWithoutTrashed(array $with = [], array $without = []): EloquentCollection
    {
        return $this->roomRepository->allWithoutTrashed($with, $without);
    }

    public function getRoomsNotIdIn(array $ids): EloquentCollection
    {
        return $this->roomRepository->getRoomsNotIdIn($ids);
    }

    public function getRoomCategoryIds(int|Room $room): Collection
    {
        return $this->roomRepository->getRoomCategoryIds($room);
    }

    public function getRoomAttributeIds(int|Room $room): Collection
    {
        return $this->roomRepository->getRoomAttributeIds($room);
    }

    public function getAdjoiningRoomIds(int|Room $room): Collection
    {
        return $this->roomRepository->getAdjoiningRoomIds($room);
    }

    public function isUserRoomAdmin(Room $room, User $user): bool
    {
        return $this->roomRepository->getUserWhereIsAdmin($room, $user->id)->count() > 0;
    }

    /**
     * @throws Throwable
     */
    public function createShowDto(
        UserService $userService,
        Room $room,
        ProjectTabService $projectTabService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        User $user
    ): ShowDto {
        [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault($user);

        $calendarData = $this->calendarDataService->createCalendarData(
            $startDate,
            $endDate,
            null,
            $user->calendar_filter,
            $room
        );

        return ShowDto::newInstance()
            ->setCalendar($calendarData['roomsWithEvents'])
            ->setDays($calendarData['days'])
            ->setEventsWithoutRoom($calendarData['eventsWithoutRoom'])
            ->setFilterOptions($calendarData['filterOptions'])
            ->setDateValue($calendarData['dateValue'])
            ->setPersonalFilters($calendarData['personalFilters'])
            ->setCalendarType($calendarData['calendarType'])
            ->setSelectedDate($calendarData['selectedDate'])
            ->setUserFilters($calendarData['user_filters'])
            ->setFirstProjectTabId($projectTabService->findFirstProjectTab()?->id)
            ->setFirstProjectCalendarTabId($projectTabService->findFirstProjectTabWithCalendarComponent()?->id)
            ->setAvailableCategories($roomCategoryService->getAll())
            ->setAvailableAttributes($roomAttributeService->getAll())
            ->setIsRoomAdmin($this->isUserRoomAdmin($room, $userService->getAuthUser()))
            ->setAvailableRooms($this->getRoomsNotIdIn([$room->id]))
            ->setRoomCategoryIds($this->getRoomCategoryIds($room))
            ->setRoomAttributeIds($this->getRoomAttributeIds($room))
            ->setAdjoiningRoomIds($this->getAdjoiningRoomIds($room))
            ->setRoom(RoomCalendarResource::make($room))
            ->setRoomCategories(CategoryIndexResource::collection($room->categories)->resolve())
            ->setRoomAttributes(AttributeIndexResource::collection($room->attributes)->resolve())
            ->setAdjoiningRooms(AdjoiningRoomIndexResource::collection($room->adjoining_rooms)->resolve())
            ->setRooms(RoomIndexWithoutEventsResource::collection($this->getAllWithoutTrashed())->resolve())
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setProjects(
                ProjectIndexAdminResource::collection($projectService->getProjectsWithAccessBudgetAndManagerUsers())
                    ->resolve()
            );
    }

    public function getFallbackRoom(): Room
    {
        if (!$fallbackRoom = $this->roomRepository->getFallbackRoom()) {
            $fallbackRoom = new Room();
            $fallbackRoom->user()->associate(User::first());
            $fallbackRoom->area()->associate(Area::first());
            $fallbackRoom->name = 'FallbackRoom Room';
            $fallbackRoom->description = 'Fallback Room';
            $fallbackRoom->fallback_room = true;
            $fallbackRoom->order = 9999;
            $this->save($fallbackRoom);
        }

        return $fallbackRoom;
    }

    public function findByName(string $name): ?Room
    {
        return $this->roomRepository->findByName($name);
    }
}
