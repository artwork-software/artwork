<?php

namespace Artwork\Modules\Room\Services;

use App\Http\Resources\MinimalShiftPlanEventResource;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Throwable;

readonly class RoomService
{
    public function __construct(
        private RoomRepository $roomRepository,
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

    public function getFilteredRooms(
        ?Carbon $startDate,
        ?Carbon $endDate,
        CalendarFilter|null $calendarFilter
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

    public function deleteAllByArea(Area $area): void
    {
        $this->roomRepository->deleteByReference($area, 'rooms');
    }

    public function getAllWithoutTrashed(array $with = [], array $without = []): EloquentCollection
    {
        return $this->roomRepository->allWithoutTrashed($with, $without);
    }

    /**
     * @return array <string, mixed>
     * @throws Throwable
     * @deprectated use EventCollectionService::collectEventsForRoom
     */
    public function collectEventsForRoom(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): array {
        return app(EventCollectionService::class)->collectEventsForRoom(
            $room,
            $calendarPeriod,
            $calendarFilter,
            $project,
            $desiresInventorySchedulingResource
        );
    }

    /**
     * @deprecated use EventCollectionService::collectEventsForRoomsOnSpecificDays
     * @return array<string, array<int, array<int, Event>>>
     */
    public function collectEventsForRoomsOnSpecificDays(
        array $desiredRooms,
        array $desiredDays,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
    ): array {

        return app(EventCollectionService::class)->collectEventsForRoomsOnSpecificDays(
            $desiredRooms,
            $desiredDays,
            $calendarFilter,
            $project
        );
    }

    /**
     * @return array<int, mixed>
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function collectEventsForRoomShift(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?UserShiftCalendarFilter $calendarFilter,
        ?Carbon $desiredDay = null
    ): array {
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

        $roomEvents = $room
            ->events()
            ->with(
                [
                    'room',
                    'creator',
                    'project',
                    'project.managerUsers',
                    'project.state',
                    'project.shiftRelevantEventTypes',
                    'shifts',
                    'shifts.craft',
                    'shifts.users',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                    'subEvents.event',
                    'subEvents.event.room',
                ]
            )
            ->without([
                'created_by',
                'shift_relevant_event_types',
                'shifts.users.calendar_settings',
                'shifts.users.calendarAbo',
                'shifts.users.shiftCalendarAbo',
            ])
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
            ->unless(empty($eventTypeIds), function ($builder) use ($eventTypeIds) {
                return $builder->where(function ($builder) use ($eventTypeIds): void {
                    $builder->whereIn('event_type_id', $eventTypeIds)
                        ->orWhereHas('subEvents', function ($builder) use ($eventTypeIds): void {
                            $builder->whereIn('event_type_id', $eventTypeIds);
                        });
                });
            })
            ->unless(!$hasAudience, fn(Builder $builder) => $builder->where('audience', true))
            ->unless(!$hasNoAudience, fn(Builder $builder) => $builder->where('audience', false))
            ->unless(!$isLoud, fn(Builder $builder) => $builder->where('is_loud', true))
            ->unless(!$isNotLoud, fn(Builder $builder) => $builder->where('is_loud', false))
            ->when(
                $desiredDay,
                fn(Builder $builder) => $builder->startAndEndTimeOverlap(
                    $desiredDay->startOfDay(),
                    $desiredDay->clone()->endOfDay()
                ),
                fn(Builder $builder) => $builder->startAndEndTimeOverlap($calendarPeriod->start, $calendarPeriod->end)
            )->get();

        return $this->convertEventsForFrontend($room, $roomEvents, $calendarPeriod);
    }

    /**
     * @return array<string, array<int, array<int, Event>>>
     */
    public function convertEventsForFrontend(
        Room $room,
        array|Collection $events,
        CarbonPeriod $calendarPeriod,
    ): array {
        $actualEvents = [];
        $eventsForRoom = static::fillPeriodWithEmptyEventData($room, $calendarPeriod);

        foreach ($events as $event) {
            $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                $calendarPeriod->start :
                $event->start_time;

            $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ?
                $calendarPeriod->end :
                $event->end_time;

            $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

            foreach ($eventPeriod as $date) {
                $dateKey = $date->format('d.m.Y');
                $actualEvents[$dateKey][] = $event;
            }
        }

        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = [
                'roomName' => $room->getAttribute('name'),
                'roomId' => $room->getAttribute('id'),
                //immediately resolve resource to free used memory
                'events' => MinimalShiftPlanEventResource::collection($value)->resolve()
            ];
        }

        return $eventsForRoom;
    }

    /**
     * @deprecated use EventCollectionService::collectEventsForRooms
     */
    public function collectEventsForRooms(
        array|Collection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): Collection {
        return app(EventCollectionService::class)->collectEventsForRooms(
            $roomsWithEvents,
            $calendarPeriod,
            $calendarFilter,
            $project,
            $desiresInventorySchedulingResource
        );
    }

    /**
     * @return array<string, array<int, array<int, Event>>>
     */
    public function collectEventsForRoomsShiftOnSpecificDays(
        RoomService $roomService,
        UserService $userService,
        array $desiredRooms,
        array $desiredDays,
        ?UserShiftCalendarFilter $userShiftCalendarFilter,
    ): array {
        [$startDate, $endDate] = $userService->getUserShiftCalendarFilterDatesOrDefault($userService->getAuthUser());
        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);
        $collectedEvents = [];

        foreach ($desiredDays as $desiredDay) {
            foreach ($desiredRooms as $roomId) {
                $room = $this->roomRepository->findOrFail($roomId);
                foreach (
                    array_filter(
                        $roomService->collectEventsForRoomShift(
                            $room,
                            $calendarPeriod,
                            $userShiftCalendarFilter,
                            Carbon::parse($desiredDay)
                        ),
                        function ($collectedEventsForRoom): bool {
                            return !empty($collectedEventsForRoom['events']);
                        }
                    ) as $collectedEventsForRoom
                ) {
                    $collectedEvents[$desiredDay][$roomId] = $collectedEventsForRoom['events'];
                }
            }
        }

        return $collectedEvents;
    }

    public function collectEventsForRoomsShift(
        array|Collection $rooms,
        array|Collection $events,
        CarbonPeriod $calendarPeriod,
    ): Collection {
        $roomEvents = collect();

        foreach ($rooms as $room) {
            $roomEvents->add(
                $this->convertEventsForFrontend(
                    $room,
                    $events->filter(
                        function ($event) use ($room): bool {
                            return $event->room_id === $room->id;
                        }
                    ),
                    $calendarPeriod,
                )
            );
        }

        return $roomEvents;
    }

    /**
     * @return array<string, mixed>
     */
    public static function fillPeriodWithEmptyEventData(
        Room $room,
        CarbonPeriod $calendarPeriod
    ): array {
        $eventsForRoom = [];
        /** @var Collection $eventsForRoom */
        foreach ($calendarPeriod as $date) {
            $eventsForRoom[$date->format('d.m.Y')] = [
                'roomName' => $room->getAttribute('name'),
                'roomId' => $room->getAttribute('id'),
                'events' => []
            ];
        }
        return $eventsForRoom;
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

    public function update(Room $room, array $attributes): Room
    {
        $this->roomRepository->update($room, $attributes);

        return $room;
    }
}
