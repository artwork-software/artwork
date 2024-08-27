<?php

namespace Artwork\Modules\Event\Services;

use App\Http\Resources\MinimalShiftPlanEventResource;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Http\Resources\MinimalInventorySchedulingEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Event\Repositories\FindsEventsWithoutRoom;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\FiltersRoomsBy;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\UserShiftCalendarFilter\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class EventCollectorService
{
    use FindsEventsWithoutRoom;

    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly EventRepository $eventRepository
    ) {
    }

    public function collectEventsForRoomsShift(
        array|Collection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?UserShiftCalendarFilter $calendarFilter
    ): Collection {
        $roomEvents = collect();

        foreach ($roomsWithEvents as $room) {
            $roomEvents->add(
                $this->collectEventsForRoomShift(
                    $room,
                    $calendarPeriod,
                    $calendarFilter
                )
            );
        }

        return $roomEvents;
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
        $eventsForRoom = $this->fillPeriodWithEmptyEventData($room, $calendarPeriod);
        $actualEvents = [];

        /** @var Builder $roomEvents */
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
            ->without(['created_by', 'shift_relevant_event_types'])
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
                fn(Builder $builder)  => $builder->startAndEndTimeOverlap(
                    $desiredDay->startOfDay(),
                    $desiredDay->clone()->endOfDay()
                ),
                fn(Builder $builder) => $builder->startAndEndTimeOverlap($calendarPeriod->start, $calendarPeriod->end)
            )->get();

        foreach ($roomEvents as $roomEvent) {
            $eventStart = $roomEvent->start_time->isBefore($calendarPeriod->start) ?
                $calendarPeriod->start :
                $roomEvent->start_time;

            $eventEnd = $roomEvent->end_time->isAfter($calendarPeriod->end) ?
                $calendarPeriod->end :
                $roomEvent->end_time;

            $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

            foreach ($eventPeriod as $date) {
                $dateKey = $date->format('d.m.Y');
                $actualEvents[$dateKey][] = $roomEvent;
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
                'roomName' => $room->getAttribute('name'),
                'roomId' => $room->getAttribute('id'),
                'events' => []
            ];
        }
        return $eventsForRoom;
    }

    /**
     * @return array <string, mixed>
     * @throws Throwable
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function collectEventsForRoom(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): array {
        $eventsForRoom = $this->fillPeriodWithEmptyEventData($room, $calendarPeriod);
        $actualEvents = [];

        $roomEventsQuery = $this->buildRoomCollectionBaseQuery(
            $room,
            $calendarFilter,
            $project,
            $calendarPeriod,
            null
        );

        foreach ($roomEventsQuery->get()->all() as $event) {
            $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                $calendarPeriod->start :
                $event->start_time;
            $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ? $calendarPeriod->end : $event->end_time;
            $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

            foreach ($eventPeriod as $date) {
                $dateKey = $date->format('d.m.Y');
                $actualEvents[$dateKey][] = $event;
            }
        }

        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = [
                'roomId' => $room->getAttribute('id'),
                //immediately resolve resource to free used memory
                'events' => $desiresInventorySchedulingResource ?
                    MinimalInventorySchedulingEventResource::collection($value)->resolve() :
                    MinimalCalendarEventResource::collection($value)->resolve()
            ];
        }

        return $eventsForRoom;
    }

    /**
     * @return array<string, array<int, array<int, Event>>>
     */
    public function collectEventsForRoomsOnSpecificDays(
        UserService $userService,
        array $desiredRooms,
        array $desiredDays,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
    ): array {
        [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault();

        $collectedEvents = [];
        foreach ($desiredDays as $desiredDay) {
            foreach ($desiredRooms as $roomId) {
                $collectedEvents[$desiredDay][$roomId] = MinimalCalendarEventResource::collection(
                    $this->buildRoomCollectionBaseQuery(
                        $this->roomRepository->findOrFail($roomId),
                        $calendarFilter,
                        $project,
                        CarbonPeriod::create($startDate, $endDate),
                        Carbon::parse($desiredDay)
                    )->get()
                )->resolve();
            }
        }

        return $collectedEvents;
    }

    /**
     * @throws Throwable
     */
    public function collectEventsForRooms(
        array|Collection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): Collection {
        $roomEvents = collect();

        foreach ($roomsWithEvents as $room) {
            $roomEvents->add(
                $this->collectEventsForRoom(
                    room: $room,
                    calendarPeriod: $calendarPeriod,
                    calendarFilter: $calendarFilter,
                    project: $project,
                    desiresInventorySchedulingResource: $desiresInventorySchedulingResource
                )
            );
        }

        return $roomEvents;
    }

    /**
     * @return array<string, array<int, array<int, Event>>>
     */
    public function collectEventsForRoomsShiftOnSpecificDays(
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
                        $this->collectEventsForRoomShift(
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

    //@todo: refactor
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    private function buildRoomCollectionBaseQuery(
        Room $room,
        ?CalendarFilter $calendarFilter,
        ?Project $project,
        ?CarbonPeriod $calendarPeriod,
        ?Carbon $date
    ): HasMany {
        $isLoud = $calendarFilter?->is_loud ?? false;
        $isNotLoud = $calendarFilter?->is_not_loud ?? false;
        $hasAudience = $calendarFilter?->has_audience ?? false;
        $hasNoAudience = $calendarFilter?->has_no_audience ?? false;
        $showAdjoiningRooms = $calendarFilter?->show_adjoining_rooms ?? false;
        $eventTypeIds = $calendarFilter?->event_types ?? null;
        $roomIds = $calendarFilter?->rooms ?? null;
        $areaIds = $calendarFilter?->areas ?? null;
        $roomAttributeIds = $calendarFilter?->room_attributes ?? null;
        $roomCategoryIds = $calendarFilter?->room_categories ?? null;

        $roomEventsQuery = Room::query()->getRelation('events')
            ->with(
                [
                    'room',
                    'creator',
                    'project',
                    'project.managerUsers',
                    'project.state',
                    'shifts',
                    'shifts.craft',
                    'shifts.users',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                    'subEvents.event',
                    'subEvents.event.room'
                ]
            )
            ->where(
                function (Builder $builder) use ($calendarPeriod, $date): void {
                    $builder->where(function (Builder $builder) use ($calendarPeriod, $date): void {
                        $builder->whereBetween('start_time', [$calendarPeriod->start, $calendarPeriod->end]);
                        $builder->when(
                            $date,
                            function (Builder $builder) use ($date): void {
                                $builder
                                    ->whereDate('start_time', '<=', $date)
                                    ->whereDate('end_time', '>=', $date);
                            }
                        );
                    })->orWhere(function (Builder $builder) use ($calendarPeriod, $date): void {
                        $builder->whereBetween('end_time', [$calendarPeriod->start, $calendarPeriod->end]);
                        $builder->when(
                            $date,
                            function (Builder $builder) use ($date): void {
                                $builder->whereDate('start_time', '<=', $date)
                                    ->whereDate('end_time', '>=', $date);
                            }
                        );
                    });
                }
            )
            ->where(function ($builder) use ($calendarPeriod): void {
                $builder->where(function ($builder) use ($calendarPeriod): void {
                    $builder->where('start_time', '<', $calendarPeriod->start)
                        ->where('end_time', '>', $calendarPeriod->end);
                })->orWhere(function ($builder) use ($calendarPeriod): void {
                    $builder->whereBetween('start_time', [$calendarPeriod->start, $calendarPeriod->end])
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
            ->unless(empty($eventTypeIds), function ($builder) use ($eventTypeIds) {
                return $builder->where(function ($builder) use ($eventTypeIds): void {
                    $builder->whereIn('event_type_id', $eventTypeIds)
                        ->orWhereHas('subEvents', function ($builder) use ($eventTypeIds): void {
                            $builder->whereIn('event_type_id', $eventTypeIds);
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
        $roomEventsQuery->orderBy('start_time');

        return $roomEventsQuery;
    }
}
