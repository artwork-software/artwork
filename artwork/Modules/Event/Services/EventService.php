<?php

namespace Artwork\Modules\Event\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Http\Controllers\ShiftFilterController;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\Event\DTOs\EventManagementDto;
use Artwork\Modules\Event\DTOs\ShiftPlanDto;
use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use Artwork\Modules\Event\Events\EventDeleted;
use Artwork\Modules\Event\Events\EventUpdated;
use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\EventComment\Models\EventComment;
use Artwork\Modules\EventComment\Services\EventCommentService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\PresetShift\Models\PresetShiftShiftsQualifications;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\Services\RoomService;
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
use Illuminate\Support\Collection as SupportCollection;
use Throwable;

readonly class EventService
{
    public function __construct(
        private EventRepository $eventRepository,
        private CalendarDataService $calendarDataService,
        private readonly WorkingHourService $workingHourService
    ) {
    }

    public function importShiftPreset(
        Event $event,
        ShiftPreset $shiftPreset,
        TimelineService $timelineService,
        ShiftService $shiftService,
        ShiftQualificationService $shiftQualificationService,
        ShiftsQualificationsService $shiftsQualificationsService,
    ): void {
        $timelineService->forceDeleteTimelines($event->timelines);
        foreach ($shiftPreset->timeline as $shiftPresetTimeline) {
            $timelineService->createFromShiftPresetTimeline($shiftPresetTimeline, $event);
        }

        $shiftService->forceDeleteShifts($event->shifts);
        /** @var PresetShift $presetShift */
        foreach ($shiftPreset->shifts as $presetShift) {
            $shift = $shiftService->createFromShiftPresetShiftForEvent($presetShift, $event);

            /** @var PresetShiftShiftsQualifications $presetShiftShiftsQualification */
            foreach ($presetShift->shiftsQualifications as $presetShiftShiftsQualification) {
                if (
                    !$shiftQualificationService->isStillAvailable(
                        $presetShiftShiftsQualification->shift_qualification_id
                    )
                ) {
                    continue;
                }

                $shiftsQualificationsService->createShiftsQualificationForShift(
                    $shift->id,
                    [
                        'shift_qualification_id' => $presetShiftShiftsQualification->shift_qualification_id,
                        'value' => $presetShiftShiftsQualification->value,
                    ]
                );
            }
        }
    }

    public function importShiftPresetForEventsOfProjectByEventType(
        ShiftPreset $shiftPreset,
        int $projectId,
        TimelineService $timelineService,
        ShiftService $shiftService,
        ShiftQualificationService $shiftQualificationService,
        ShiftsQualificationsService $shiftsQualificationsService,
    ): void {
        foreach (
            $this->eventRepository->getEventsByProjectIdAndEventTypeId(
                $projectId,
                $shiftPreset->event_type_id
            ) as $eventByProjectIdAndEventTypeId
        ) {
            $this->importShiftPreset(
                $eventByProjectIdAndEventTypeId,
                $shiftPreset,
                $timelineService,
                $shiftService,
                $shiftQualificationService,
                $shiftsQualificationsService
            );
        }
    }

    public function delete(
        Event $event,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
        ProjectTabService $projectTabService,
    ): void {
        if (!empty($event->project_id)) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($event->project->id)
                    ->setTranslationKey('Schedule deleted')
            );
        }

        $this->createEventDeletedNotificationsForProjectManagers($event, $notificationService, $projectTabService);
        $this->createEventDeletedNotification($event, $notificationService, $projectTabService);

        $eventCommentService->deleteEventComments($event->comments);
        $timelineService->deleteTimelines($event->timelines);
        $shiftService->deleteShifts(
            $event->shifts,
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService
        );
        $subEventService->deleteSubEvents($event->subEvents);

        broadcast(new OccupancyUpdated())->toOthers();

        $notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

        $deletedEvent = new EventDeleted(
            $event->room_id,
            $event->start_time,
            $event->is_series ?
                $event->series->end_date :
                $event->end_time
        );
        $this->eventRepository->delete($event);
        broadcast($deletedEvent)->toOthers();
    }

    public function deleteAll(
        Collection|array $events,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
        ProjectTabService $projectTabService,
    ): void {
        /** @var Event $event */
        foreach ($events as $event) {
            if (!empty($event->project_id)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($event->project->id)
                        ->setTranslationKey('Schedule deleted')
                );
            }

            $this->createEventDeletedNotificationsForProjectManagers($event, $notificationService, $projectTabService);
            $this->createEventDeletedNotification($event, $notificationService, $projectTabService);

            $eventCommentService->deleteEventComments($event->comments);
            $timelineService->deleteTimelines($event->timelines);
            $shiftService->deleteShifts(
                $event->shifts,
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService
            );
            $subEventService->deleteSubEvents($event->subEvents);

            broadcast(new OccupancyUpdated())->toOthers();

            $notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

            $this->eventRepository->delete($event);
        }
    }

    public function restore(
        Event $event,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
    ): void {
        $this->eventRepository->restore($event);
        if (!empty($event->project_id)) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($event->project->id)
                    ->setTranslationKey('Schedule restored')
            );
        }
        $eventCommentService->restoreEventComments($event->comments()->onlyTrashed()->get());
        $timelineService->restoreTimelines($event->timelines()->onlyTrashed()->get());
        $shiftService->restoreShifts(
            $event->shifts()->onlyTrashed()->get(),
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService
        );
        $subEventService->restoreSubEvents($event->subEvents()->onlyTrashed()->get());

        broadcast(new OccupancyUpdated())->toOthers();
    }

    public function forceDeleteAll(
        Collection|array $events,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
    ): void {
        /** @var Event $event */
        foreach ($events as $event) {
            $shifts = Shift::onlyTrashed()->where('event_id', $event->id)->get();
            $timelines = Timeline::onlyTrashed()->where('event_id', $event->id)->get();
            $comments = EventComment::onlyTrashed()->where('event_id', $event->id)->get();
            $subEvents = SubEvent::onlyTrashed()->where('event_id', $event->id)->get();

            $eventCommentService->deleteEventComments($comments);
            $timelineService->forceDeleteTimelines($timelines);
            $shiftService->forceDeleteShifts($shifts);
            $subEventService->forceDeleteSubEvents($subEvents);

            $notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

            $this->eventRepository->forceDelete($event);
        }
    }

    public function restoreAll(
        Collection|array $events,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
    ): void {
        /** @var Event $event */
        foreach ($events as $event) {
            $this->eventRepository->restore($event);
            if (!empty($event->project_id)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($event->project->id)
                        ->setTranslationKey('Schedule restored')
                );
            }

            $eventCommentService->restoreEventComments($event->comments()->onlyTrashed()->get());
            $timelineService->restoreTimelines($event->timelines()->onlyTrashed()->get());
            $shiftService->restoreShifts(
                $event->shifts()->onlyTrashed()->get(),
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService
            );
            $subEventService->restoreSubEvents($event->subEvents()->onlyTrashed()->get());

            broadcast(new OccupancyUpdated())->toOthers();
        }
    }

    private function createEventDeletedNotificationsForProjectManagers(
        Event $event,
        NotificationService $notificationService,
        ProjectTabService $projectTabService,
    ): void {
        if (is_null($event->project) || $event->project->managerUsers->isEmpty()) {
            return;
        }

        $notificationService->setIcon('blue');
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_ANSWER);

        foreach ($event->project->managerUsers as $projectManager) {
            if ($projectManager->id === $event->creator->id) {
                continue;
            }

            $notificationTitle = __('notification.event.deleted', [], $projectManager->language);
            $notificationService->setTitle($notificationTitle);
            $notificationService->setBroadcastMessage([
                'id' => random_int(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle,
            ]);
            $notificationService->setDescription([
                1 => [
                    'type' => 'link',
                    'title' => $event->room?->name,
                    'href' => $event->room ? route('rooms.show', $event->room->id) : null,
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type->name . ', ' . $event->eventName,
                    'href' => null,
                ],
                3 => [
                    'type' => 'link',
                    'title' => $event->project?->name ?? '',
                    'href' => $event->project ? route(
                        'projects.tab',
                        [
                            $event->project->id,
                            $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::CALENDAR
                            ),
                        ]
                    ) : null,
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null,
                ],
            ]);
            $notificationService->setNotificationTo($projectManager);
            $notificationService->createNotification();
        }
    }

    private function createEventDeletedNotification(
        Event $event,
        NotificationService $notificationService,
        ProjectTabService $projectTabService,
    ): void {
        $notificationService->setIcon('blue');
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_ANSWER);
        $notificationTitle = __('notification.event.deleted', [], $event->creator->language);
        $notificationService->setTitle($notificationTitle);
        $notificationService->setBroadcastMessage([
            'id' => random_int(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle,
        ]);
        $notificationService->setDescription([
            1 => [
                'type' => 'link',
                'title' => $event->room->name ?? '',
                'href' => $event->room ? route('rooms.show', $event->room->id) : null,
            ],
            2 => [
                'type' => 'string',
                'title' => $event->event_type->name . ', ' . $event->eventName,
                'href' => null,
            ],
            3 => [
                'type' => 'link',
                'title' => $event->project?->name ?? '',
                'href' => $event->project ? route(
                    'projects.tab',
                    [
                        $event->project->id,
                        $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                            ProjectTabComponentEnum::CALENDAR
                        ),
                    ]
                ) : null,
            ],
            4 => [
                'type' => 'string',
                'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                    Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                'href' => null,
            ],
        ]);
        $notificationService->setNotificationTo($event->creator);
        $notificationService->createNotification();
    }

    /**
     * @return array<int, mixed>
     */
    public function getDaysWithEventsWhereUserHasShiftsWithTotalPlannedWorkingHours(
        int $userId,
        Carbon $startDate,
        Carbon $endDate,
    ): array {
        $totalPlannedWorkingHours = 0;
        $eventsWithPlannedWorkingHours = [];

        /** @var Event $event */
        foreach (
            $this->eventRepository->getEventsWhereUserHasShiftsInPeriod(
                $userId,
                CarbonPeriod::create($startDate, $endDate)
            ) as $event
        ) {
            $earliestStart = null;
            $latestEnd = null;
            $plannedWorkingHours = 0;
            $totalBreakMinutes = 0;

            /** @var Shift $shift */
            foreach ($event['shifts'] as $shift) {
                $start = Carbon::parse($shift['start']);
                $end = Carbon::parse($shift['end']);

                $earliestStart = ($earliestStart === null || $start->lt($earliestStart)) ?
                    $start :
                    $earliestStart;

                $latestEnd = ($latestEnd === null || $end->gt($latestEnd)) ?
                    $end :
                    $latestEnd;

                $totalBreakMinutes += $shift['break_minutes'];
            }

            if ($earliestStart !== null && $latestEnd !== null) {
                $plannedWorkingHours = max(
                    ($earliestStart->diffInMinutes($latestEnd) - $totalBreakMinutes) / 60,
                    0
                );
            }

            $totalPlannedWorkingHours += $plannedWorkingHours;

            $eventsWithPlannedWorkingHours[] = [
                'event' => $event->setAttribute('daysOfShifts', $event->getDaysOfShifts()),
                'plannedWorkingHours' => $plannedWorkingHours,
            ];
        }

        return [
            $eventsWithPlannedWorkingHours,
            $totalPlannedWorkingHours,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function getDaysWithEventsWhereFreelancerHasShiftsWithTotalPlannedWorkingHours(
        int $freelancerId,
        Carbon $startDate,
        Carbon $endDate,
    ): array {
        $totalPlannedWorkingHours = 0;
        $eventsWithPlannedWorkingHours = [];

        foreach (
            $this->eventRepository->getEventsWhereFreelancerHasShiftsInPeriod(
                $freelancerId,
                CarbonPeriod::create($startDate, $endDate)
            ) as $event
        ) {
            $earliestStart = null;
            $latestEnd = null;
            $plannedWorkingHours = 0;
            $totalBreakMinutes = 0;

            /** @var Shift $shift */
            foreach ($event['shifts'] as $shift) {
                $start = Carbon::parse($shift['start']);
                $end = Carbon::parse($shift['end']);

                $earliestStart = ($earliestStart === null || $start->lt($earliestStart)) ?
                    $start :
                    $earliestStart;

                $latestEnd = ($latestEnd === null || $end->gt($latestEnd)) ?
                    $end :
                    $latestEnd;

                $totalBreakMinutes += $shift['break_minutes'];
            }

            if ($earliestStart !== null && $latestEnd !== null) {
                $plannedWorkingHours = max(
                    ($earliestStart->diffInMinutes($latestEnd) - $totalBreakMinutes) / 60,
                    0
                );
            }

            $totalPlannedWorkingHours += $plannedWorkingHours;

            $eventsWithPlannedWorkingHours[] = [
                'event' => $event->setAttribute('daysOfShifts', $event->getDaysOfShifts()),
                'plannedWorkingHours' => $plannedWorkingHours,
            ];
        }

        return [
            $eventsWithPlannedWorkingHours,
            $totalPlannedWorkingHours,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function getDaysWithEventsWhereServiceProviderHasShiftsWithTotalPlannedWorkingHours(
        int $serviceProviderId,
        Carbon $startDate,
        Carbon $endDate,
    ): array {
        $totalPlannedWorkingHours = 0;
        $eventsWithPlannedWorkingHours = [];

        foreach (
            $this->eventRepository->getEventsWhereServiceProviderHasShiftsInPeriod(
                $serviceProviderId,
                CarbonPeriod::create($startDate, $endDate)
            ) as $event
        ) {
            $plannedWorkingHours = 0;

            /** @var Shift $shift */
            foreach ($event['shifts'] as $shift) {
                $start = Carbon::parse($shift['start']);
                $end = Carbon::parse($shift['end']);

                $totalWorkingMinutes = 0;
                $totalWorkingMinutes += $start->diffInMinutes($end);
                $plannedWorkingHours += max($totalWorkingMinutes / 60, 0);
            }

            $totalPlannedWorkingHours += $plannedWorkingHours;

            $eventsWithPlannedWorkingHours[] = [
                'event' => $event->setAttribute('daysOfShifts', $event->getDaysOfShifts()),
                'plannedWorkingHours' => $plannedWorkingHours,
            ];
        }

        return [
            $eventsWithPlannedWorkingHours,
            $totalPlannedWorkingHours,
        ];
    }

    public function getShiftPlanDto(
        UserService $userService,
        FreelancerService $freelancerService,
        ServiceProviderService $serviceProviderService,
        RoomService $roomService,
        CraftService $craftService,
        FilterService $filterService,
        ShiftFilterController $shiftFilterController,
        ShiftQualificationService $shiftQualificationService,
        DayServicesService $dayServicesService,
        User $user,
        ProjectTabService $projectTabService,
    ): ShiftPlanDto {
        [$startDate, $endDate] = $userService->getUserShiftCalendarFilterDatesOrDefault($user);

        $periodArray = [];
        foreach (($calendarPeriod = CarbonPeriod::create($startDate, $endDate)) as $period) {
            if ($period->isMonday()) {
                $periodArray[] = [
                    'is_extra_row' => true,
                    'week_number' => $period->weekOfYear,
                ];
            }

            $holidays = Holiday::where(function ($query) use ($period): void {
                $query->where(function ($q) use ($period): void {
                    $q->whereDate('date', '<=', $period->format('Y-m-d'))
                        ->whereDate('end_date', '>=', $period->format('Y-m-d'));
                })->orWhere(function ($q) use ($period): void {
                    $q->where('yearly', true)
                        ->whereMonth('date', $period->month)
                        ->whereDay('end_date', $period->day);
                });
            })->with('subdivisions')->get();
            $periodArray[] = [
                'day' => $period->format('d.m.'),
                'day_string' => $period->shortDayName,
                'is_weekend' => $period->isWeekend(),
                'full_day' => $period->format('d.m.Y'),
                'short_day' => $period->format('d.m'),
                'without_format' => $period->format('Y-m-d'),
                'week_number' => $period->weekOfYear,
                'is_monday' => $period->isMonday(),
                'month_number' => $period->month,
                'is_sunday' => $period->isSunday(),
                'is_first_day_of_month' => $period->isSameDay($period->copy()->startOfMonth()),
                'add_week_separator' => $period->isSunday(),
                'holidays' => $holidays->map(function ($holiday) {
                    return [
                        'name' => $holiday->name,
                        'type' => $holiday->type,
                        'start_date' => $holiday->startDate,
                        'end_date' => $holiday->endDate,
                        'color' => $holiday->color,
                        'subdivisions' => $holiday->subdivisions->pluck('name'), // Subdivision-Namen sammeln
                    ];
                }),
            ];
        }

        $events = $this->eventRepository->getEventsWhereHasShiftsStartAndEndTimeOverlapWithUsers(
            $startDate,
            $endDate
        );

        // filter by EventTypeId
        if ($userService->getAuthUser()->shift_calendar_filter->event_types) {
            $events = $events->filter(function (Event $event) use ($userService): bool {
                return in_array($event->event_type_id, $userService->getAuthUser()->shift_calendar_filter->event_types);
            });
        }

        $filteredRooms = $roomService->getFilteredRooms(
            $startDate,
            $endDate,
            $userService->getAuthUser()->shift_calendar_filter
        );

        return ShiftPlanDto::newInstance()
            ->setHistory($this->getEventShiftsHistoryChanges())
            ->setCrafts($craftService->getAll())
            ->setShiftPlan(
                $roomService->collectEventsForRoomsShift(
                    $filteredRooms,
                    $events,
                    $calendarPeriod
                )
            )
            ->setRooms($filteredRooms)
            ->setDays($periodArray)
            ->setFilterOptions(
                $filterService->getCalendarFilterDefinitions()
            )
            ->setUserFilters($userService->getAuthUser()->shift_calendar_filter)
            ->setDateValue([$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->setPersonalFilters($shiftFilterController->index())
            ->setUsersForShifts(
                $this->workingHourService->getUsersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    UserShiftPlanResource::class,
                    true,
                    null
                )
            )
            ->setFreelancersForShifts(
                $freelancerService->getFreelancersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    FreelancerShiftPlanResource::class,
                    true,
                    $user
                )
            )
            ->setServiceProvidersForShifts(
                $serviceProviderService->getServiceProvidersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    ServiceProviderShiftPlanResource::class,
                    $user
                )
            )
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setDayServices($dayServicesService->getAll())
            ->setFirstProjectShiftTabId(
                $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            )
            ->setShiftPlanWorkerSortEnumNames(
                array_map(
                    function (ShiftPlanWorkerSortEnum $enum): string {
                        return $enum->name;
                    },
                    ShiftPlanWorkerSortEnum::cases()
                ),
            );
    }

    /**
     * @return array<int, mixed>
     */
    public function getEventShiftsHistoryChanges(): array
    {
        $q = Change::query();
        $q->where('model_type', Shift::class);
        $q->orderBy('created_at', 'desc');
        $historyArray = [];
        $q->get()->each(function (Change $history) use (&$historyArray): void {
            $historyArray[] = [
                'changes' => json_decode($history->changes),
                'created_at' => $history->created_at->diffInHours() < 24
                    ? $history->created_at->diffForHumans()
                    : $history->created_at->format('d.m.Y, H:i'),
            ];
        });

        return $historyArray;
    }

    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createEventManagementDtoForAtAGlance(
        CalendarService $calendarService,
        RoomService $roomService,
        UserService $userService,
        FilterService $filterService,
        ProjectTabService $projectTabService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
        ProjectService $projectService,
        ?Project $project = null,
    ): EventManagementDto {
        $user = $userService->getAuthUser();
        $userCalendarFilter = $user->getAttribute('calendar_filter');
        $userCalendarSettings = $user->getAttribute('calendar_settings');

        //today is used if project calendar is opened and no events are given as project calendar
        //do not rely on user calendar filter dates
        $today = Carbon::now();

        if (
            !($useProjectTimePeriod = $userCalendarSettings->getAttribute('use_project_time_period')) &&
            !$project
        ) {
            [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault();
        } else {
            if (!$project && $useProjectTimePeriod) {
                $project = $projectService->findById($userCalendarSettings->getAttribute('time_period_project_id'));

                [$startDate, $endDate] = [
                    ($firstEventInProject = $projectService->getFirstEventInProject($project)) ?
                        $firstEventInProject->getAttribute('start_time')->startOfDay() :
                        null,
                    $firstEventInProject && ($lastEventInProject = $projectService->getLastEventInProject($project)) ?
                        $lastEventInProject->getAttribute('end_time')->endOfDay() :
                        null,
                ];
            } else {
                [$startDate, $endDate] = [
                    ($firstEventInProject = $projectService->getFirstEventInProject($project)) ?
                        $firstEventInProject->getAttribute('start_time')->startOfDay() :
                        $today->startOfDay(),
                    $firstEventInProject && ($lastEventInProject = $projectService->getLastEventInProject($project)) ?
                        $lastEventInProject->getAttribute('end_time')->endOfDay() :
                        $today->endOfDay(),
                ];
            }
        }

        $desiredProjectHasNoEvents = $useProjectTimePeriod && !$startDate && !$endDate;

        $eventManagementDto = EventManagementDto::newInstance()
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setDateValue(
                $desiredProjectHasNoEvents ?
                    [] :
                    [
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d'),
                    ]
            )
            ->setEventStatuses(EventStatus::orderBy('order')->get())
            ->setCalendarType(
                $desiredProjectHasNoEvents ? 'individual' :
                    (
                    $startDate->format('d.m.Y') === $endDate->format('d.m.Y') ?
                        'daily' :
                        'individual'
                    )
            )
            ->setSelectedDate(
                $desiredProjectHasNoEvents ?
                    null :
                    (
                    $startDate?->format('Y-m-d') === $endDate?->format('Y-m-d') ?
                        $startDate?->format('Y-m-d') :
                        null
                    )
            )
            ->setEventsWithoutRoom(
                empty($room) ?
                    CalendarEventResource::collection(
                        $this->getEventsWithoutRoom(
                            $project,
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
                                'subEvents.event.room',
                            ]
                        )
                    )->resolve() :
                    []
            )
            ->setEventsAtAGlance(
                $desiredProjectHasNoEvents ?
                    null :
                    $calendarService->getEventsAtAGlance(
                        $startDate,
                        $endDate,
                        $useProjectTimePeriod ? null : $project
                    )
            )
            ->setRooms(
                $roomService->getFilteredRooms(
                    $startDate,
                    $endDate,
                    $userCalendarFilter
                )
            )
            ->setFilterOptions(
                $filterService->getCalendarFilterDefinitions()
            )
            ->setAreas($areaService->getAll())
            ->setPersonalFilters($filterService->getPersonalFilter())
            ->setUserFilters($user->getAttribute('calendar_filter'))
            ->setFirstProjectTabId($projectTabService->getFirstProjectTabId())
            ->setFirstProjectCalendarTabId(
                $projectTabService
                    ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR)
            )
            ->setFirstProjectShiftTabId(
                $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            );

        if ($useProjectTimePeriod) {
            $eventManagementDto->setProjectNameUsedForProjectTimePeriod($project->getAttribute('name'));
        }

        return $eventManagementDto;
    }

    /**
     * @throws Throwable
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createEventManagementDto(
        RoomService $roomService,
        UserService $userService,
        FilterService $filterService,
        ProjectTabService $projectTabService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
        ProjectService $projectService,
        ?Project $project = null,
    ): EventManagementDto {
        $user = $userService->getAuthUser();
        $userCalendarFilter = $user->getAttribute('calendar_filter');
        $userCalendarSettings = $user->getAttribute('calendar_settings');

        //today is used if project calendar is opened and no events are given as project calendar
        //do not rely on user calendar filter dates
        $today = Carbon::now();

        if (
            !($useProjectTimePeriod = $userCalendarSettings->getAttribute('use_project_time_period')) &&
            !$project
        ) {
            [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault();
        } else {
            if (!$project && $useProjectTimePeriod) {
                $project = $projectService->findById($userCalendarSettings->getAttribute('time_period_project_id'));

                [$startDate, $endDate] = [
                    ($firstEventInProject = $projectService->getFirstEventInProject($project)) ?
                        $firstEventInProject->getAttribute('start_time')->startOfDay() :
                        null,
                    $firstEventInProject && (
                        $latestEndingEventInProject = $projectService->getLatestEndingEventInProject($project)
                    ) ? $latestEndingEventInProject->getAttribute('end_time')->endOfDay() :
                        null,
                ];
            } else {
                [$startDate, $endDate] = [
                    ($firstEventInProject = $projectService->getFirstEventInProject($project)) ?
                        $firstEventInProject->getAttribute('start_time')->startOfDay() :
                        $today->startOfDay(),
                    $firstEventInProject && (
                        $latestEndingEventInProject = $projectService->getLatestEndingEventInProject($project)
                    ) ?
                        $latestEndingEventInProject->getAttribute('end_time')->endOfDay() :
                        $today->endOfDay(),
                ];
            }
        }

        if ($useProjectTimePeriod && !$startDate && !$endDate) {
            $showCalendar = [
                'roomsWithEvents' => SupportCollection::make(),
                'days' => [],
                'dateValue' => [],
                'calendarType' => 'individual',
                'selectedDate' => '',
                'eventsWithoutRoom' => [],
                'filterOptions' => $filterService->getCalendarFilterDefinitions(),
                'personalFilters' => $filterService->getPersonalFilter(),
                'user_filters' => $userService->getAuthUser()->calendar_filter,
            ];
        } else {
            $showCalendar = $this->calendarDataService->createCalendarData(
                startDate: $startDate,
                endDate: $endDate,
                calendarFilter: $userCalendarFilter,
                project: !$useProjectTimePeriod ? $project : null
            );
        }

        $eventManagementDto = EventManagementDto::newInstance()
            ->setEventStatuses(EventStatus::orderBy('order')->get())
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setCalendar($showCalendar['roomsWithEvents'])
            ->setDays($showCalendar['days'])
            ->setDateValue($showCalendar['dateValue'])
            ->setCalendarType($showCalendar['calendarType'])
            ->setSelectedDate($showCalendar['selectedDate'])
            ->setEventsWithoutRoom($showCalendar['eventsWithoutRoom'])
            ->setRooms(
                $roomService->getFilteredRooms(
                    $startDate,
                    $endDate,
                    $userCalendarFilter
                )
            )
            ->setAreas($areaService->getAll())
            ->setFilterOptions($showCalendar["filterOptions"])
            ->setPersonalFilters($showCalendar['personalFilters'])
            ->setUserFilters($showCalendar['user_filters'])
            ->setFirstProjectTabId($projectTabService->getFirstProjectTabId())
            ->setFirstProjectCalendarTabId(
                $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR)
            )
            ->setFirstProjectShiftTabId(
                $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            );


        if ($useProjectTimePeriod) {
            $eventManagementDto->setProjectNameUsedForProjectTimePeriod($project->getAttribute('name'));
        }

        return $eventManagementDto;
    }

    /** @return Event[]|Collection */
    public function getAll(): Collection|array
    {
        return $this->eventRepository->getAll();
    }

    public function getEarliestStartTime(Event $event): Carbon
    {
        $earliestStartTime = $event->start_time;

        foreach ($event->timelines as $timeline) {
            $timelineStart = Carbon::parse($timeline->start)->format('H:i:s');
            $startDateTime = Carbon::parse(
                $timeline->start_date->format('Y-m-d') . '
             ' . $timelineStart
            );
            if ($startDateTime->isBefore($earliestStartTime)) {
                $earliestStartTime = $startDateTime;
            }
        }

        foreach ($event->shifts as $shift) {
            $shiftStart = Carbon::parse($shift->start)->format('H:i:s');
            $shiftStartDate = Carbon::parse($shift->start_date)->format('Y-m-d');
            $startDateTime = Carbon::parse($shiftStartDate . ' ' . $shiftStart);

            if ($startDateTime->isBefore($earliestStartTime)) {
                $earliestStartTime = $startDateTime;
            }
        }

        return $earliestStartTime;
    }

    // get latest end time of all timelines and shifts of an event

    public function getLatestEndTime(Event $event): Carbon
    {
        $latestEndTime = $event->end_time;

        foreach ($event->timelines as $timeline) {
            $timelineEnd = Carbon::parse($timeline->end)->format('H:i:s');
            $endDateTime = Carbon::parse($timeline->end_date->format('Y-m-d') . ' ' . $timelineEnd);
            if ($endDateTime->isAfter($latestEndTime)) {
                $latestEndTime = $endDateTime;
            }
        }

        foreach ($event->shifts as $shift) {
            $shiftEnd = Carbon::parse($shift->end)->format('H:i:s');
            $shiftEndDate = Carbon::parse($shift->end_date)->format('Y-m-d');
            $endDateTime = Carbon::parse($shiftEndDate . ' ' . $shiftEnd);
            if ($endDateTime->isAfter($latestEndTime)) {
                $latestEndTime = $endDateTime;
            }
        }

        return $latestEndTime;
    }


    public function createSeriesEvent(
        $startDate,
        $endDate,
        $request,
        $series,
        $projectId,
        $user,
    ): Model|Event {
        $event = new Event();

        $event->name = $request->title;
        $event->eventName = $request->eventName;
        $event->description = $request->description;
        $event->start_time = $startDate;
        $event->end_time = $endDate;
        $event->occupancy_option = $request->isOption;
        $event->audience = $request->audience;
        $event->is_loud = $request->isLoud;
        $event->event_type_id = $request->eventTypeId;
        $event->room_id = $request->roomId;
        $event->project_id = $projectId ?: null;
        $event->is_series = true;
        $event->series_id = $series->id;
        $event->allDay = $request->allDay;
        $event->creator()->associate($user);
        return $this->eventRepository->save($event);
    }

    public function save(Event $event): Event|Model
    {
        $originalStartTime = $event->getOriginal('start_time');
        $originalEndTime = $event->getOriginal('end_time');
        $originalRoomId = $event->getOriginal('room_id');

        $event = $this->eventRepository->save($event);
        if ($originalStartTime && $originalEndTime) {
            broadcast(new EventUpdated($originalRoomId, $originalStartTime, $originalEndTime))->toOthers();
        }
        broadcast(new EventUpdated(
            $event->room_id,
            $event->start_time,
            $event->is_series ?
                $event->series->end_date :
            $event->end_time
        ))->toOthers();
        return $event;
    }


    public function findEventById(
        int $eventId,
    ): ?Event {
        return $this->eventRepository->findById($eventId);
    }

    /** @deprecated use EventCollectionService */
    public function getEventsWithoutRoom(int|Project|null $project = null, array|null $with = null): Collection
    {
        return $this->eventRepository->getEventsWithoutRoom($project, $with);
    }

    /**
     * @param Carbon $day
     * @param string|null $startTime
     * @param string|null $endTime
     * @return array{Carbon, Carbon, bool}
     */
    private function processEventTimes(Carbon $day, ?string $startTime, ?string $endTime): array
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


    public function createBulkEvent(
        array $event,
        Project $project,
        int $userId,
    ): void {
        $day = Carbon::parse($event['day']);
        [$startTime, $endTime, $allDay] = $this->processEventTimes(
            $day,
            $event['start_time'] ?? null,
            $event['end_time'] ?? null
        );

        $project->events()->create([
            'eventName' => $event['name'] ?? '',
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'allDay' => $allDay,
            'event_type_id' => $event['type']['id'],
            'room_id' => $event['room']['id'],
            'event_status_id' => $event['status']['id'],
        ]);
    }

    public function updateBulkEvent(
        SupportCollection $data,
        Event $event,
    ): void {
        $day = Carbon::parse($data['day']);
        [$startTime, $endTime, $allDay] = $this->processEventTimes(
            $day,
            $data['start_time'] ?? null,
            $data['end_time'] ?? null
        );

        $this->eventRepository->update($event, [
            'eventName' => $data['name'],
            'name' => $data['name'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'allDay' => $allDay,
            'event_type_id' => $data['type']['id'],
            'room_id' => $data['room']['id'],
            'event_status_id' => $data['status']['id'],
        ]);
    }

    public function getOrderBySubQueryBuilder(string $column, string $direction): Builder
    {
        return $this->eventRepository->getOrderBySubQueryBuilder($column, $direction);
    }

    public function update(Event $event, $attributes): Event
    {
        $this->eventRepository->update($event, $attributes);

        return $event;
    }
}
