<?php

namespace Artwork\Modules\Event\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\ShiftFilterController;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\Event\DTOs\EventManagementDto;
use Artwork\Modules\Event\DTOs\ShiftPlanDto;
use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Http\Resources\MinimalInventorySchedulingEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\EventComment\Models\EventComment;
use Artwork\Modules\EventComment\Services\EventCommentService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\PresetShift\Models\PresetShiftShiftsQualifications;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\SubEvent\Models\SubEvent;
use Artwork\Modules\SubEvent\Services\SubEventService;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Throwable;

class EventCollectionService
{

    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly EventRepository $eventRepository
    ) {
    }

    public function collectEventsForRooms(
        array|\Illuminate\Support\Collection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
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

    public function collectEventsForRoom(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): array
    {
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

    public function collectEventsForRoomsOnSpecificDays(
        array $desiredRooms,
        array $desiredDays,
        ?CalendarFilter $calendarFilter,
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
                        $builder->when(
                            $calendarPeriod,
                            function (Builder $builder) use ($calendarPeriod): void {
                                $builder->whereBetween(
                                    'start_time',
                                    [
                                        $calendarPeriod->start,
                                        $calendarPeriod->end
                                    ]
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
                                $builder->whereBetween(
                                    'end_time',
                                    [
                                        $calendarPeriod->start,
                                        $calendarPeriod->end
                                    ]
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
            ->when(
                $calendarPeriod,
                function (Builder $builder) use ($calendarPeriod): void {
                    $builder->where(
                        function ($builder) use ($calendarPeriod): void {
                            $builder->where(function ($builder) use ($calendarPeriod): void {
                                $builder->where('start_time', '<', $calendarPeriod->start)
                                    ->where('end_time', '>', $calendarPeriod->end);
                            })->orWhere(function ($builder) use ($calendarPeriod): void {
                                $builder->whereBetween('start_time', [$calendarPeriod->start, $calendarPeriod->end])
                                    ->orWhereBetween('end_time', [$calendarPeriod->start, $calendarPeriod->end]);
                            });
                        }
                    );
                }
            )
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
