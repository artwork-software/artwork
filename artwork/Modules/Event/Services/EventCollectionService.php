<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Http\Resources\MinimalInventorySchedulingEventResource;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class EventCollectionService
{

    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly EventRepository $eventRepository
    ) {
    }

    public function collectEventsForRooms(
        array|SupportCollection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?UserFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): SupportCollection {
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
     * @param Room $room
     * @param CarbonPeriod $calendarPeriod
     * @param CalendarFilter|null $calendarFilter
     * @param Project|null $project
     * @param bool|null $desiresInventorySchedulingResource
     * @return array<string, mixed>
     */
    public function collectEventsForRoom(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?UserFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): array {
        $eventsForRoom = RoomService::fillPeriodWithEmptyEventData($room, $calendarPeriod);
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

    public function getEventsWithoutRoom(int|Project|null $project = null, array|null $with = null): Collection
    {
        return $this->eventRepository->getEventsWithoutRoom($project, $with);
    }

    /**
     * @param array $desiredRooms
     * @param array $desiredDays
     * @param CalendarFilter|null $calendarFilter
     * @param Project|null $project
     * @return array<string, mixed>
     */
    public function collectEventsForRoomsOnSpecificDays(
        array $desiredRooms,
        array $desiredDays,
        ?UserFilter $calendarFilter,
        ?Project $project = null,
    ): array {
        $collectedEvents = [];
        foreach ($desiredDays as $desiredDay) {
            foreach ($desiredRooms as $roomId) {
                $collectedEvents[$desiredDay][$roomId] = MinimalCalendarEventResource::collection(
                    $this->buildRoomCollectionBaseQuery(
                        $this->roomRepository->findOrFail($roomId),
                        $calendarFilter,
                        $project,
                        null,
                        Carbon::parse($desiredDay)
                    )->get()
                )->resolve();
            }
        }

        return $collectedEvents;
    }

    /**
     * @param Room $room
     * @param CalendarFilter|null $calendarFilter
     * @param Project|null $project
     * @param CarbonPeriod|null $calendarPeriod
     * @param Carbon|null $date
     * @return HasMany
     */
    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    private function buildRoomCollectionBaseQuery(
        Room $room,
        ?UserFilter $calendarFilter,
        ?Project $project,
        ?CarbonPeriod $calendarPeriod,
        ?Carbon $date
    ): HasMany {
        $eventTypeIds = $calendarFilter?->event_type_ids ?? null;
        $roomIds = $calendarFilter?->room_ids ?? null;
        $areaIds = $calendarFilter?->area_ids ?? null;
        $roomAttributeIds = $calendarFilter?->room_attribute_ids ?? null;
        $roomCategoryIds = $calendarFilter?->room_category_ids ?? null;

        $roomEventsQuery = Room::query()->getRelation('events')
            ->with(
                [
                    'room',
                    'creator',
                    'project',
                    'project.managerUsers',
                    'project.status',
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
                        $builder->when(
                            $calendarPeriod,
                            function (Builder $builder) use ($calendarPeriod): void {
                                //see Event scopeStartAndEndTimeOverlap
                                $builder->startAndEndTimeOverlap(
                                    $calendarPeriod->start,
                                    $calendarPeriod->end
                                );
                            }
                        );
                        $builder->when(
                            $date,
                            function (Builder $builder) use ($date): void {
                                $builder
                                    ->whereDate('start_time', '<=', $date)
                                    ->whereDate('end_time', '>=', $date);
                            }
                        );
                    })->orWhere(function (Builder $builder) use ($calendarPeriod, $date): void {
                        $builder->when(
                            $calendarPeriod,
                            function (Builder $builder) use ($calendarPeriod): void {
                                //see Event scopeStartAndEndTimeOverlap
                                $builder->startAndEndTimeOverlap(
                                    $calendarPeriod->start,
                                    $calendarPeriod->end
                                );
                            }
                        );
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
            ->when($project, fn(Builder $builder) => $builder->where('project_id', $project->id))
            ->when($room, fn(Builder $builder) => $builder->where('room_id', $room->id))
            ->unless(
                empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds),
                fn(Builder $builder) => $builder->whereHas('room', fn(Builder $roomBuilder) => $roomBuilder
                    ->when($roomIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                    ->when($areaIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
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


        $roomEventsQuery->where('deleted_at', null);

        // order $roomEventsQuery by start_time
        $roomEventsQuery->orderBy('start_time');

        return $roomEventsQuery;
    }
}
