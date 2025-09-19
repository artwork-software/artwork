<?php

namespace Artwork\Modules\Event\Repositories;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Events\RemoveEvent;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class EventRepository extends BaseRepository
{
    public function __construct(private readonly Event $event, private readonly CarbonService $carbonService)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
         return $this->event->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->event->newModelQuery();
    }

    public function getEventsByProjectIdAndEventTypeId(int $projectId, int $eventTypeId): Collection
    {
        return Event::byProjectId($projectId)->byEventTypeId($eventTypeId)->get();
    }

    public function getEventsWhereUserHasShiftsInPeriod(int $userId, CarbonPeriod $carbonPeriod): Collection
    {
        return Event::query()
            ->with(
                [
                    'room',
                    'shifts' => function (HasMany $query) use ($userId): void {
                        $query->whereRelation('users', 'user_id', $userId);
                        $query->orderBy('start_date');
                        $query->orderBy('start');
                        $query->orderBy('end_date');
                        $query->orderBy('end');
                    },
                    'shifts.users',
                    'shifts.users.dayServices',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                ]
            )
            ->whereHas(
                'shifts.users',
                function (Builder $builder) use ($userId): void {
                    $builder->where('user_id', $userId);
                }
            )
            ->whereBetween('start_time', [$carbonPeriod->start, $carbonPeriod->end])
            ->whereBetween('end_time', [$carbonPeriod->start, $carbonPeriod->end])
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get();
    }

    public function getAll(): Collection
    {
        return Event::all();
    }

    public function getEventsWhereFreelancerHasShiftsInPeriod(
        int $freelancerId,
        CarbonPeriod $carbonPeriod
    ): Collection {
        return Event::query()
            ->with(
                [
                    'room',
                    'shifts' => function (HasMany $query) use ($freelancerId): void {
                        $query->whereRelation('freelancer', 'freelancer_id', $freelancerId);
                        $query->orderBy('start_date');
                        $query->orderBy('start');
                        $query->orderBy('end_date');
                        $query->orderBy('end');
                    },
                    'shifts.users',
                    'shifts.users.dayServices',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                ]
            )
            ->whereHas(
                'shifts.freelancer',
                function (Builder $builder) use ($freelancerId): void {
                    $builder->where('freelancer_id', $freelancerId);
                }
            )
            ->whereBetween('start_time', $carbonPeriod)
            ->whereBetween('end_time', $carbonPeriod)
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get();
    }

    public function getEventsWhereServiceProviderHasShiftsInPeriod(
        int $serviceProviderId,
        CarbonPeriod $carbonPeriod
    ): Collection {
        return Event::query()
            ->with(
                [
                    'room',
                    'shifts' => function (HasMany $query) use ($serviceProviderId): void {
                        $query->whereRelation(
                            'serviceProvider',
                            'service_provider_id',
                            $serviceProviderId
                        );
                        $query->orderBy('start_date');
                        $query->orderBy('start');
                        $query->orderBy('end_date');
                        $query->orderBy('end');
                    },
                    'shifts.users',
                    'shifts.users.dayServices',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                ]
            )
            ->whereHas(
                'shifts.serviceProvider',
                function (Builder $builder) use ($serviceProviderId): void {
                    $builder->where('service_provider_id', $serviceProviderId);
                }
            )
            ->whereBetween('start_time', $carbonPeriod)
            ->whereBetween('end_time', $carbonPeriod)
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get();
    }

    public function getEventsWhereHasShiftsStartAndEndTimeOverlap(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->getEventsWhereHasShiftsStartAndEndTimeOverlapQuery($startDate, $endDate)->get();
    }

    public function getEventsWhereHasShiftsStartAndEndTimeOverlapWithUsers(
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $query = $this->getEventsWhereHasShiftsStartAndEndTimeOverlapQuery($startDate, $endDate);
        $query->with(['shifts.users',
            'project',
            'project.managerUsers',
            'project.state',
            'shifts.shiftsQualifications'
        ]);
        $query->without([
            'shifts.users.calendar_settings',
            'shifts.calendarAbo',
            'shifts.users.shiftCalendarAbo'
        ]);
        return $query->get();
    }

    private function getEventsWhereHasShiftsStartAndEndTimeOverlapQuery(Carbon $startDate, Carbon $endDate): Builder
    {
        $query = Event::query();
        $query->with(['shifts', 'event_type', 'room'])
//            ->whereHas(
//                'shifts',
//                function (Builder $builder): void {
//                    $builder->whereNotNull('shifts.id')->without('crafts');
//                }
//            )
            ->startAndEndTimeOverlap($startDate, $endDate)
            ->without(['series']);

        return $query;
    }

    public function findById(int $id): ?Event
    {
        return Event::find($id);
    }

    public function getEventsWithoutRoom(int|Project $project = null, ?array $with = null): Collection
    {
        /** @var Builder $builder */
        $builder = Event::query()->hasNoRoom();

        if ($project) {
            $builder->byProjectId(($project instanceof Project) ? $project->getAttribute('id') : $project);
        }

        if ($with) {
            $builder->with($with);
        }

        return $builder->get();
    }

    public function getOrderBySubQueryBuilder(string $column, string $direction): Builder
    {
        return $this->getNewModelQuery()->getOrderBySubQueryBuilder($column, $direction);
    }

    public function getEventsForExport(SupportCollection $exportConfiguration): Collection
    {
        $query = $this->getNewModelQuery();

        $desiresTimespanExport = $exportConfiguration->get('desiresTimespanExport', false);
        $conditional = $exportConfiguration->get('conditional', []);
        $filter = $exportConfiguration->get('filter', []);

        $query
            ->with(
                [
                    'eventStatus',
                    'project',
                    'project.users',
                    'project.categories',
                    'project.genres',
                    'project.sectors',
                ]
            )
            //handle conditionals
            ->when(
                $desiresTimespanExport,
                function (Builder $query) use ($conditional): void {
                    $startAndEndTime = [
                        $this->carbonService->create($conditional['dateStart'])->startOfDay(),
                        $this->carbonService->create($conditional['dateEnd'])->endOfDay(),
                    ];

                    $query->whereBetween('start_time', $startAndEndTime);
                    $query->orWhereBetween('end_time', $startAndEndTime);
                }
            )
            ->when(
                !$desiresTimespanExport,
                function (Builder $query) use ($conditional): void {
                    $query->whereIn('project_id', $conditional['projects']);
                }
            )
            //handle rooms
            ->when(
                count(($rooms = $filter['rooms'])) > 0,
                function (Builder $query) use ($rooms): void {
                    $query->whereIn('room_id', $rooms);
                }
            )
            //handle room categories
            ->when(
                count(($roomCategories = $filter['roomCategories'])) > 0,
                function (Builder $query) use ($roomCategories): void {
                    $query->whereRelation(
                        'room.categories',
                        function (Builder $query) use ($roomCategories): void {
                            $query->whereIn('room_category_id', $roomCategories);
                        }
                    );
                }
            )
            //handle room attributes
            ->when(
                count(($roomAttributes = $filter['roomAttributes'])) > 0,
                function (Builder $query) use ($roomAttributes): void {
                    $query->whereRelation(
                        'room.attributes',
                        function (Builder $query) use ($roomAttributes): void {
                            $query->whereIn('room_attribute_id', $roomAttributes);
                        }
                    );
                }
            )
            //handle areas
            ->when(
                count(($areas = $filter['areas'])) > 0,
                function (Builder $query) use ($areas): void {
                    $query->whereRelation(
                        'room',
                        function (Builder $query) use ($areas): void {
                            $query->whereIn('area_id', $areas);
                        }
                    );
                }
            )
            //handle event types
            ->when(
                count(($eventTypes = $filter['eventTypes'])) > 0,
                function (Builder $query) use ($eventTypes): void {
                    $query->whereIn('event_type_id', $eventTypes);
                }
            )
            //@todo: bei exportanpassung berücksichtigen jgl
//            ->when(
//                count(($eventAttributes = $filter['eventAttributes'])) > 0,
//                function (Builder $query) use ($eventAttributes): void {
//                    foreach ($eventAttributes as $eventAttribute) {
//                        $query->when(
//                            $eventAttribute === FilterService::LOUD ||
//                            $eventAttribute === FilterService::NOT_LOUD,
//                            function (Builder $query) use ($eventAttribute): void {
//                                $query->where('is_loud', ($eventAttribute === FilterService::LOUD));
//                            }
//                        );
//                        $query->when(
//                            $eventAttribute === FilterService::WITH_AUDIENCE ||
//                            $eventAttribute === FilterService::WITHOUT_AUDIENCE,
//                            function (Builder $query) use ($eventAttribute): void {
//                                $query->where('audience', ($eventAttribute === FilterService::WITH_AUDIENCE));
//                            }
//                        );
//                    }
//                }
//            )
                ->where('deleted_at', null)
            ->orderBy('start_time');

        return $query->get();
    }

    public function attachEventProperty(Event $event, EventProperty $eventProperty): Event
    {
        $event->eventProperties()->attach($eventProperty->getAttribute('id'));

        return $event;
    }

    public function updateEvents(SupportCollection $eventIds, array $updates, $selectedDay, $selectedStartTime, $selectedEndTime): void
    {
        DB::transaction(function () use ($eventIds, $updates, $selectedDay, $selectedStartTime, $selectedEndTime) {
            foreach ($eventIds as $eventId) {
                $event = Event::findOrFail($eventId);
                $event->update($updates);

                if ($selectedDay || $selectedStartTime || $selectedEndTime) {
                    $startTime = $event->start_time;
                    $endTime = $event->end_time;
                    $allDay = $event->allDay;

                    if ($selectedDay && !$selectedStartTime && !$selectedEndTime) {
                        // Day-only change: keep existing times but move to new day
                        $startTime = Carbon::parse($selectedDay)->setTimeFrom($startTime);
                        $endTime = Carbon::parse($selectedDay)->setTimeFrom($endTime);
                    } elseif ($selectedStartTime || $selectedEndTime) {
                        // Time-only change: update times and convert to non-all-day event
                        $day = optional($startTime)->toDateString() ?? Carbon::now()->toDateString();
                        if ($selectedStartTime) $startTime = Carbon::parse("$day $selectedStartTime");
                        if ($selectedEndTime) $endTime = Carbon::parse("$day $selectedEndTime");
                        $allDay = false; // Convert to timed event when times are provided
                    }

                    if ($selectedDay && $selectedStartTime && $selectedEndTime) {
                        // Day and time change: use processEventTimes for proper handling
                        [$startTime, $endTime, $allDay] = $this->processEventTimes(
                            Carbon::parse($selectedDay),
                            $selectedStartTime,
                            $selectedEndTime
                        );
                    }

                    $event->update([
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'allDay' => $allDay,
                    ]);
                }

                broadcast(new \Artwork\Modules\Event\Events\BulkEventChanged(
                    $event->fresh(),
                    'updated'
                ));

                broadcast(new EventCreated($event->fresh(), $event->room_id));
            }
        });
    }

    public function deleteEvents(SupportCollection $eventIds): void
    {
        DB::transaction(function () use ($eventIds) {
            Event::whereIn('id', $eventIds)->delete();
        });
    }

    /**
     * @param Carbon $day
     * @param string|null $startTime
     * @param string|null $endTime
     * @return array{Carbon, Carbon, bool}
     */
    public function processEventTimes(Carbon $day, ?string $startTime, ?string $endTime): array
    {
        $endDay = clone $day;
        $allDay = !$startTime || !$endTime;

        if (!$allDay) {
            $startTime = Carbon::parse($startTime);
            $endTime = Carbon::parse($endTime);

            if ($endTime->lt($startTime) || $endTime->eq($startTime)) {
                $endDay->addDay();
            }

            $day->setTimeFromTimeString($startTime->toTimeString());
            $endDay->setTimeFromTimeString($endTime->toTimeString());
        } else {
            $day->startOfDay();
            $endDay->endOfDay();
        }
        return [$day, $endDay, $allDay];
    }
}
