<?php

namespace Artwork\Modules\Room\Services;

use App\Http\Controllers\FilterController;
use App\Http\Resources\CalendarShowEventInShiftPlan;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Category\Http\Resources\CategoryIndexResource;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Http\Resources\CalendarShowEventResource;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

readonly class RoomService
{
    public function __construct(private RoomRepository $roomRepository)
    {
    }

    public function save(Room $room): Room
    {
        return $this->roomRepository->save($room);
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

    public function getFilteredRooms(
        Carbon $startDate,
        Carbon $endDate,
        UserShiftCalendarFilter|UserCalendarFilter|null $calendarFilter
    ): EloquentCollection {
        return $this->roomRepository->getFilteredRoomsBy(
            $calendarFilter?->rooms,
            $calendarFilter?->room_attributes,
            $calendarFilter?->areas,
            $calendarFilter?->room_categories,
            $calendarFilter?->adjoining_not_loud,
            $calendarFilter?->adjoining_no_audience,
            $startDate,
            $endDate
        );
    }


    public function checkTemporaryChanges(
        $roomId,
        $oldTemporary,
        $newTemporary,
        $oldStartDate,
        $newStartDate,
        $oldEndDate,
        $newEndDate,
        ChangeService $changeService
    ): void {
        if ($oldTemporary && !$newTemporary) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Room::class)
                    ->setModelId($roomId)
                    ->setTranslationKey('Temporary time period deleted')
            );
            return;
        }
        if ($newTemporary && !$oldTemporary) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Room::class)
                    ->setModelId($roomId)
                    ->setTranslationKey('Temporary time period added')
            );
            return;
        }

        // add check if temporary not changed
        if ($oldTemporary && $newTemporary) {
            if ($oldStartDate !== $newStartDate || $oldEndDate !== $newEndDate) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($roomId)
                        ->setTranslationKey('Temporary time period changed')
                );
            }
        }
    }

    public function checkCategoryChanges($roomId, $oldCategories, $newCategories, ChangeService $changeService): void
    {
        $oldCategoryIds = [];
        $oldCategoryNames = [];
        $newCategoryIds = [];

        foreach ($oldCategories as $oldCategory) {
            $oldCategoryIds[$oldCategory->id] = $oldCategory->id;
            $oldCategoryNames[$oldCategory->id] = $oldCategory->name;
        }

        foreach ($newCategories as $newCategory) {
            $newCategoryIds[] = $newCategory->id;
            if (!in_array($newCategory->id, $oldCategoryIds)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($roomId)
                        ->setTranslationKey('Added category')
                        ->setTranslationKeyPlaceholderValues([$newCategory->name])
                );
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId) {
            if (!in_array($oldCategoryId, $newCategoryIds)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($roomId)
                        ->setTranslationKey('Deleted category')
                        ->setTranslationKeyPlaceholderValues([$oldCategoryNames[$oldCategoryId]])
                );
            }
        }
    }

    public function checkAttributeChanges($roomId, $oldAttributes, $newAttributes, ChangeService $changeService): void
    {
        $oldAttributeIds = [];
        $oldAttributeNames = [];
        $newAttributeIds = [];

        foreach ($oldAttributes as $oldAttribute) {
            $oldAttributeIds[] = $oldAttribute->id;
            $oldAttributeNames[$oldAttribute->id] = $oldAttribute->name;
        }

        foreach ($newAttributes as $newAttribute) {
            $newAttributeIds[] = $newAttribute->id;
            if (!in_array($newAttribute->id, $oldAttributeIds)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($roomId)
                        ->setTranslationKey('Added attribute')
                        ->setTranslationKeyPlaceholderValues([$newAttribute->name])
                );
            }
        }

        foreach ($oldAttributeIds as $oldAttributeId) {
            if (!in_array($oldAttributeId, $newAttributeIds)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($roomId)
                        ->setTranslationKey('Deleted attribute')
                        ->setTranslationKeyPlaceholderValues([$oldAttributeNames[$oldAttributeId]])
                );
            }
        }
    }

    public function checkTitleChanges($roomId, $oldTitle, $newTitle, ChangeService $changeService): void
    {
        if ($oldTitle !== $newTitle) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Room::class)
                    ->setModelId($roomId)
                    ->setTranslationKey('Room name has been changed')
            );
        }
    }

    public function checkDescriptionChanges(
        $roomId,
        $oldDescription,
        $newDescription,
        ChangeService $changeService
    ): void {
        // check changes in room description
        if ($oldDescription !== $newDescription) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Room::class)
                    ->setModelId($roomId)
                    ->setTranslationKey('Description has been changed')
            );
        }
    }

    public function checkAdjoiningRoomChanges(
        $roomId,
        $oldAdjoiningRooms,
        $newAdjoiningRooms,
        ChangeService $changeService
    ): void {
        $newAdjoiningRoomIds = [];
        $oldAdjoiningRoomIds = [];
        $oldAdjoiningRoomName = [];

        foreach ($oldAdjoiningRooms as $oldAdjoiningRoom) {
            $oldAdjoiningRoomIds[] = $oldAdjoiningRoom->id;
            $oldAdjoiningRoomName[$oldAdjoiningRoom->id] = $oldAdjoiningRoom->name;
        }

        foreach ($newAdjoiningRooms as $newAdjoiningRoom) {
            $newAdjoiningRoomIds[] = $newAdjoiningRoom->id;
            if (!in_array($newAdjoiningRoom->id, $oldAdjoiningRoomIds)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($roomId)
                        ->setTranslationKey('Adjoining room was added')
                        ->setTranslationKeyPlaceholderValues([$newAdjoiningRoom->name])
                );
            }
        }

        foreach ($oldAdjoiningRoomIds as $oldAdjoiningRoomId) {
            if (!in_array($oldAdjoiningRoomId, $newAdjoiningRoomIds)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($roomId)
                        ->setTranslationKey('Adjoining room has been removed')
                        ->setTranslationKeyPlaceholderValues([$oldAdjoiningRoomName[$oldAdjoiningRoomId]])
                );
            }
        }
    }

    public function checkMemberChanges(
        Room $room,
        $roomAdminsBefore,
        $roomAdminsAfter,
        NotificationService $notificationService,
        ChangeService $changeService
    ): void {
        $roomAdminIdsBefore = [];
        $roomAdminIdsAfter = [];
        foreach ($roomAdminsBefore as $roomAdminBefore) {
            $roomAdminIdsBefore[] = $roomAdminBefore->id;
        }

        foreach ($roomAdminsAfter as $roomAdminAfter) {
            $roomAdminIdsAfter[] = $roomAdminAfter->id;
            if (!in_array($roomAdminAfter->id, $roomAdminIdsBefore)) {
                $user = User::find($roomAdminAfter->id);
                $notificationTitle = __(
                    'notifications.room.leader.add',
                    ['room' => $room->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationService->setTitle($notificationTitle);
                $notificationService->setIcon('green');
                $notificationService->setPriority(3);
                $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_CHANGED);
                $notificationService->setBroadcastMessage($broadcastMessage);
                $notificationService->setNotificationTo($user);
                $notificationService->createNotification();
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($room->id)
                        ->setTranslationKey('Added as room admin')
                        ->setTranslationKeyPlaceholderValues([$user->first_name])
                );
            }
        }

        // check if user remove as room admin
        foreach ($roomAdminIdsBefore as $roomAdminBefore) {
            if (!in_array($roomAdminBefore, $roomAdminIdsAfter)) {
                $user = User::find($roomAdminBefore);
                $notificationTitle = __(
                    'notifications.room.leader.remove',
                    ['room' => $room->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => random_int(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $notificationService->setTitle($notificationTitle);
                $notificationService->setIcon('red');
                $notificationService->setPriority(2);
                $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_CHANGED);
                $notificationService->setBroadcastMessage($broadcastMessage);
                $notificationService->setNotificationTo($user);
                $notificationService->createNotification();
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($room->id)
                        ->setTranslationKey('Removed as room admin')
                        ->setTranslationKeyPlaceholderValues([$user->first_name])
                );
            }
        }
    }

    public function deleteAllByArea(Area $area): void
    {
        $this->roomRepository->deleteByReference($area, 'rooms');
    }

    public function getAllWithoutTrashed(array $with = []): EloquentCollection
    {
        return $this->roomRepository->allWithoutTrashed($with);
    }

    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function collectEventsForRoom(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?Project $project = null,
        ?CalendarFilter $calendarFilter = null,
    ): Collection {
        if (!$calendarFilter) {
            $calendarFilter = new \stdClass();
        }

        $isLoud = $calendarFilter->is_loud ?? false;
        $isNotLoud = $calendarFilter->is_not_loud ?? false;
        $hasAudience = $calendarFilter->has_audience ?? false;
        $hasNoAudience = $calendarFilter->has_no_audience ?? false;
        $showAdjoiningRooms = $calendarFilter->show_adjoining_rooms ?? false;
        $eventTypeIds = $calendarFilter->event_types ?? null;
        $roomIds = $calendarFilter->rooms ?? null;
        $areaIds = $calendarFilter->areas ?? null;
        $roomAttributeIds = $calendarFilter->room_attributes ?? null;
        $roomCategoryIds = $calendarFilter->room_categories ?? null;
        $eventsForRoom = $this->fillPeriodWithEmptyEventData($room, $calendarPeriod);
        $actualEvents = [];

        $roomEventsQuery = $room->events()
            ->where(function ($query) use ($calendarPeriod): void {
                $query->where(function ($q) use ($calendarPeriod): void {
                    // Events, die vor der Periode beginnen und nach der Periode enden
                    $q->where('start_time', '<', $calendarPeriod->start)
                        ->where('end_time', '>', $calendarPeriod->end);
                })->orWhere(function ($q) use ($calendarPeriod): void {
                    // Events, die innerhalb der Periode starten oder enden
                    $q->whereBetween('start_time', [$calendarPeriod->start, $calendarPeriod->end])
                        ->orWhereBetween('end_time', [$calendarPeriod->start, $calendarPeriod->end]);
                });
            })
            ->when($project, fn(Builder $builder) => $builder->where('project_id', $project->id))
            ->when($room, fn(Builder $builder) => $builder->where('room_id', $room->id))
            ->unless(
                empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds),
                fn(Builder $builder) => $builder->whereHas('room', fn(Builder $roomBuilder) => $roomBuilder
                    ->when($roomIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                    ->when($areaIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                    ->when($showAdjoiningRooms, fn(Builder $roomBuilder) => $roomBuilder->with('adjoining_rooms'))
                    ->when($roomAttributeIds, fn(Builder $roomBuilder) => $roomBuilder
                        ->whereHas('attributes', fn(Builder $roomAttributeBuilder) => $roomAttributeBuilder
                            ->whereIn('room_attributes.id', $roomAttributeIds)))
                    ->when($roomCategoryIds, fn(Builder $roomBuilder) => $roomBuilder
                        ->whereHas('categories', fn(Builder $roomCategoryBuilder) => $roomCategoryBuilder
                            ->whereIn('room_categories.id', $roomCategoryIds)))
                    ->without(['admins']))
            )
            ->unless(empty($eventTypeIds), function ($query) use ($eventTypeIds) {
                return $query->where(function ($query) use ($eventTypeIds): void {
                    $query->whereIn('event_type_id', $eventTypeIds)
                        ->orWhereHas('subEvents', function ($query) use ($eventTypeIds): void {
                            $query->whereIn('event_type_id', $eventTypeIds);
                        });
                });
            });

        if ($hasAudience && !$hasNoAudience) {
            $roomEventsQuery->where('audience', true);
        }

        if (!$hasAudience && $hasNoAudience) {
            $roomEventsQuery->where('audience', false);
        }

        if ($isLoud && !$isNotLoud) {
            $roomEventsQuery->where('is_loud', true);
        }

        if (!$isLoud && $isNotLoud) {
            $roomEventsQuery->where('is_loud', false);
        }

        // order $roomEventsQuery by start_time
        $roomEventsQuery->orderBy('start_time', 'asc');

        $roomEventsQuery->each(function (Event $event) use (&$actualEvents, $calendarPeriod): void {
            // Erstelle einen Zeitraum für das Event, der innerhalb der gewünschten Periode liegt
            $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                $calendarPeriod->start :
                $event->start_time;
            $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ? $calendarPeriod->end : $event->end_time;
            $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

            foreach ($eventPeriod as $date) {
                $dateKey = $date->format('d.m.Y');
                $actualEvents[$dateKey][] = $event;
            }
        });

        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = [
                'roomName' => $room->name,
                'events' => CalendarShowEventResource::collection($value)
            ];
        }
        return collect($eventsForRoom);
    }

    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function collectEventsForRoomShift(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?Project $project = null,
        $shiftPlan = false
    ): Collection {
        $user = Auth::user();
        if (!$shiftPlan) {
            $calendarFilter = $user->calendar_filter()->first();
        } else {
            $calendarFilter = $user->shift_calendar_filter()->first();
        }

        $isLoud = $calendarFilter->is_loud ?? false;
        $isNotLoud = $calendarFilter->is_not_loud ?? false;
        $hasAudience = $calendarFilter->has_audience ?? false;
        $hasNoAudience = $calendarFilter->has_no_audience ?? false;
        $showAdjoiningRooms = $calendarFilter->show_adjoining_rooms ?? false;
        $eventTypeIds = $calendarFilter->event_types ?? null;
        $roomIds = $calendarFilter->rooms ?? null;
        $areaIds = $calendarFilter->areas ?? null;
        $roomAttributeIds = $calendarFilter->room_attributes ?? null;
        $roomCategoryIds = $calendarFilter->room_categories ?? null;
        $eventsForRoom = $this->fillPeriodWithEmptyEventData($room, $calendarPeriod);
        $actualEvents = [];


        $room->events()->with('shifts')
            ->without(['created_by', 'shift_relevant_event_types']) // Lade die Schichten der Events vor
            /*->whereHas('shifts', function ($query) use ($calendarPeriod): void {
                $query->where(function ($q) use ($calendarPeriod): void {
                    // Schichten, die vor der Periode beginnen und nach der Periode enden
                    $q->where('start_date', '<', $calendarPeriod->start)
                        ->where('end_date', '>', $calendarPeriod->end);
                })->orWhere(function ($q) use ($calendarPeriod): void {
                    // Schichten, die innerhalb der Periode starten oder enden
                    $q->whereBetween('start_date', [$calendarPeriod->start, $calendarPeriod->end])
                        ->orWhereBetween('end_date', [$calendarPeriod->start, $calendarPeriod->end]);
                });
            })*/
            // Weitere Bedingungen und Filter wie vorher
            ->when($project, fn(Builder $builder) => $builder->where('project_id', $project->id))
            ->when($project, fn(Builder $builder) => $builder->where('project_id', $project->id))
            ->when($room, fn(Builder $builder) => $builder->where('room_id', $room->id))
            ->unless(
                empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds),
                fn(Builder $builder) => $builder->whereHas('room', fn(Builder $roomBuilder) => $roomBuilder
                    ->when($roomIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('id', $roomIds))
                    ->when($areaIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                    ->when($showAdjoiningRooms, fn(Builder $roomBuilder) => $roomBuilder->with('adjoining_rooms'))
                    ->when($roomAttributeIds, fn(Builder $roomBuilder) => $roomBuilder
                        ->whereHas('attributes', fn(Builder $roomAttributeBuilder) => $roomAttributeBuilder
                            ->whereIn('room_attributes.id', $roomAttributeIds)))
                    ->when($roomCategoryIds, fn(Builder $roomBuilder) => $roomBuilder
                        ->whereHas('categories', fn(Builder $roomCategoryBuilder) => $roomCategoryBuilder
                            ->whereIn('room_categories.id', $roomCategoryIds)))
                    ->without(['admins']))
            )
            ->unless(empty($eventTypeIds), function ($query) use ($eventTypeIds) {
                return $query->where(function ($query) use ($eventTypeIds): void {
                    $query->whereIn('event_type_id', $eventTypeIds)
                        ->orWhereHas('subEvents', function ($query) use ($eventTypeIds): void {
                            $query->whereIn('event_type_id', $eventTypeIds);
                        });
                });
            })
            ->unless(!$hasAudience, fn(Builder $builder) => $builder->where('audience', true))
            ->unless(!$hasNoAudience, fn(Builder $builder) => $builder->where('audience', false))
            ->unless(!$isLoud, fn(Builder $builder) => $builder->where('is_loud', true))
            ->unless(!$isNotLoud, fn(Builder $builder) => $builder->where('is_loud', false))
            ->each(function (Event $event) use (&$actualEvents, $calendarPeriod): void {
                // Erstelle einen Zeitraum für das Event, der innerhalb der gewünschten Periode liegt
                $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                    $calendarPeriod->start :
                    $event->start_time;

                $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ? $calendarPeriod->end : $event->end_time;

                $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

                foreach ($eventPeriod as $date) {
                    $dateKey = $date->format('d.m.Y');
                    $actualEvents[$dateKey][] = $event;
                }
            });

        foreach ($actualEvents as $key => $value) {
            // check if $value is already in the array $eventsForRoom[$key]['events] if yes then skip
            if (isset($eventsForRoom[$key])) {
                $eventsForRoom[$key]['events'] = CalendarShowEventInShiftPlan::collection(
                    collect($eventsForRoom[$key]['events'])->merge($value)->unique()
                );
                continue;
            }
        }
        return collect($eventsForRoom);
    }


    public function collectEventsForRooms(
        array|Collection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
        $shiftPlan = false
    ): Collection {
        $roomEvents = collect();

        foreach ($roomsWithEvents as $room) {
            $roomEvents->add(
                $this->collectEventsForRoom($room, $calendarPeriod, $project, $calendarFilter)
            );
        }
        return $roomEvents;
    }

    public function collectEventsForRoomsShift(
        array|Collection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?Project $project = null,
        $shiftPlan = false
    ): Collection {
        $roomEvents = collect();

        foreach ($roomsWithEvents as $room) {
            $roomEvents->add($this->collectEventsForRoomShift($room, $calendarPeriod, $project, $shiftPlan));
        }
        return $roomEvents;
    }

    /**
     * @return array<string, mixed>
     */
    private function fillPeriodWithEmptyEventData(
        Room $room,
        CarbonPeriod $calendarPeriod
    ): array {
        $eventsForRoom = [];
        /** @var Collection $eventsForRoom */
        foreach ($calendarPeriod as $date) {
            $eventsForRoom[$date->format('d.m.Y')] = [
                'roomName' => $room->name,
                'events' => CalendarShowEventResource::collection([])
            ];
        }
        return $eventsForRoom;
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

    public function createShowDto(
        UserService $userService,
        Room $room,
        CalendarService $calendarService,
        FilterService $filterService,
        FilterController $filterController,
        ProjectTabService $projectTabService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        AreaService $areaService,
        ?CalendarFilter $calendarFilter,
    ): ShowDto {
        [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault($calendarFilter);

        $calendarData = $calendarService->createCalendarData(
            $startDate,
            $endDate,
            $userService,
            $filterService,
            $filterController,
            $this,
            $roomCategoryService,
            $roomAttributeService,
            $eventTypeService,
            $areaService,
            $projectService,
            $calendarFilter,
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
