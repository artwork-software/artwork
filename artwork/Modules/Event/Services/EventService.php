<?php

namespace Artwork\Modules\Event\Services;

use App\Http\Controllers\FilterController;
use App\Http\Controllers\ShiftFilterController;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\Event\DTOs\EventManagementDto;
use Artwork\Modules\Event\DTOs\ShiftPlanDto;
use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
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
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\SubEvent\Services\SubEventService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

readonly class EventService
{
    public function __construct(private EventRepository $eventRepository)
    {
    }

    public function importShiftPreset(
        Event $event,
        ShiftPreset $shiftPreset,
        TimelineService $timelineService,
        ShiftService $shiftService,
        ShiftQualificationService $shiftQualificationService,
        ShiftsQualificationsService $shiftsQualificationsService
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
                        'value' => $presetShiftShiftsQualification->value
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
        ShiftsQualificationsService $shiftsQualificationsService
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
        ProjectTabService $projectTabService
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

        $this->eventRepository->delete($event);
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
        ProjectTabService $projectTabService
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
        SubEventService $subEventService
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
        NotificationService $notificationService
    ): void {
        /** @var Event $event */
        foreach ($events as $event) {
            $eventCommentService->deleteEventComments($event->comments);
            $timelineService->forceDeleteTimelines($event->timelines);
            $shiftService->forceDeleteShifts($event->shifts);
            $subEventService->forceDeleteSubEvents($event->subEvents);

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
        SubEventService $subEventService
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
        ProjectTabService $projectTabService
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
                'message' => $notificationTitle
            ]);
            $notificationService->setDescription([
                1 => [
                    'type' => 'link',
                    'title' => $event->room?->name,
                    'href' => $event->room ? route('rooms.show', $event->room->id) : null
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $event->project?->name ?? '',
                    'href' => $event->project ? route(
                        'projects.tab',
                        [
                            $event->project->id,
                            $projectTabService->findFirstProjectTabWithCalendarComponent()?->id
                        ]
                    ) : null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ]
            ]);
            $notificationService->setNotificationTo($projectManager);
            $notificationService->createNotification();
        }
    }

    private function createEventDeletedNotification(
        Event $event,
        NotificationService $notificationService,
        ProjectTabService $projectTabService
    ): void {
        $notificationService->setIcon('blue');
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_ANSWER);
        $notificationTitle = __('notification.event.deleted', [], $event->creator->language);
        $notificationService->setTitle($notificationTitle);
        $notificationService->setBroadcastMessage([
            'id' => random_int(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ]);
        $notificationService->setDescription([
            1 => [
                'type' => 'link',
                'title' => $event->room->name ?? '',
                'href' => $event->room ? route('rooms.show', $event->room->id) : null
            ],
            2 => [
                'type' => 'string',
                'title' => $event->event_type->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $event->project?->name ?? '',
                'href' => $event->project ? route(
                    'projects.tab',
                    [
                        $event->project->id,
                        $projectTabService->findFirstProjectTabWithCalendarComponent()?->id
                    ]
                ) : null
            ],
            4 => [
                'type' => 'string',
                'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                    Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                'href' => null
            ]
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
        Carbon $endDate
    ): array {
        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $events = $this->getEventsWhereUserHasShiftsFilteredByDateOfShifts($userId, $date);

            $earliestStart = null;
            $latestEnd = null;
            $plannedWorkingHours = 0;
            $totalBreakMinutes = 0;

            foreach ($events->all() as $event) {
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
            }

            if ($earliestStart !== null && $latestEnd !== null) {
                $plannedWorkingHours = max(
                    ($earliestStart->diffInMinutes($latestEnd) - $totalBreakMinutes) / 60,
                    0
                );
            }

            $daysWithEvents[$date->format('Y-m-d')] = [
                'day' => $date->format('d.m.'),
                'day_string' => $date->shortDayName,
                'full_day' => $date->format('d.m.Y'),
                'short_day' => $date->format('d.m'),
                'events' => $events,
                'plannedWorkingHours' => $plannedWorkingHours,
                'is_monday' => $date->isMonday(),
                'week_number' => $date->weekOfYear,
                'month_number' => $date->month,
            ];

            $totalPlannedWorkingHours += $plannedWorkingHours;
        }

        return [
            $daysWithEvents,
            $totalPlannedWorkingHours
        ];
    }

    public function getEventsWhereUserHasShiftsFilteredByDateOfShifts(int $userId, Carbon $date): Collection
    {
        return $this->eventRepository
            ->getEventsWhereUserHasShifts($userId)
            ->filter(
                function ($event) use ($date) {
                    return in_array($date->format('d.m.Y'), $event->getDaysOfShifts());
                }
            );
    }

    /**
     * @return array<int, mixed>
     */
    public function getDaysWithEventsWhereFreelancerHasShiftsWithTotalPlannedWorkingHours(
        int $freelancerId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $events = $this->getEventsWhereFreelancerHasShiftsFilteredByDateOfShifts($freelancerId, $date);

            $plannedWorkingHours = 0;
            $earliestStart = null;
            $latestEnd = null;
            $totalBreakMinutes = 0;

            foreach ($events->all() as $event) {
                $shifts = $event['shifts'];

                foreach ($shifts as $shift) {
                    $start = Carbon::parse($shift['start']);
                    $end = Carbon::parse($shift['end']);
                    $breakMinutes = $shift['break_minutes'];

                    if ($earliestStart === null || $start->lt($earliestStart)) {
                        $earliestStart = $start;
                    }
                    if ($latestEnd === null || $end->gt($latestEnd)) {
                        $latestEnd = $end;
                    }

                    $totalBreakMinutes += $breakMinutes;
                }
            }

            if ($earliestStart !== null && $latestEnd !== null) {
                $totalWorkingMinutes = $earliestStart->diffInMinutes($latestEnd) - $totalBreakMinutes;
                $plannedWorkingHours = max($totalWorkingMinutes / 60, 0);
            }

            $daysWithEvents[$date->format('Y-m-d')] = [
                'day' => $date->format('d.m.'),
                'day_string' => $date->shortDayName,
                'full_day' => $date->format('d.m.Y'),
                'short_day' => $date->format('d.m'),
                'events' => $events,
                'plannedWorkingHours' => $plannedWorkingHours,
                'is_monday' => $date->isMonday(),
                'week_number' => $date->weekOfYear,
                'month_number' => $date->month,
            ];

            $totalPlannedWorkingHours += $plannedWorkingHours;
        }

        return [$daysWithEvents, $totalPlannedWorkingHours];
    }

    public function getEventsWhereFreelancerHasShiftsFilteredByDateOfShifts(int $freelancerId, Carbon $date): Collection
    {
        return $this->eventRepository
            ->getEventsWhereFreelancerHasShifts($freelancerId)
            ->filter(
                function ($event) use ($date) {
                    return in_array($date->format('d.m.Y'), $event->getDaysOfShifts());
                }
            );
    }

    /**
     * @return array<int, mixed>
     */
    public function getDaysWithEventsWhereServiceProviderHasShiftsWithTotalPlannedWorkingHours(
        int $serviceProviderId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $events = $this->getEventsWhereServiceProviderHasShiftsFilteredByDateOfShifts($serviceProviderId, $date);
            $plannedWorkingHours = 0;

            foreach ($events as $event) {
                $shifts = $event['shifts'];
                foreach ($shifts as $shift) {
                    $start = Carbon::parse($shift['start']);
                    $end = Carbon::parse($shift['end']);

                    $totalWorkingMinutes = 0;
                    $totalWorkingMinutes += $start->diffInMinutes($end);
                    $plannedWorkingHours += max($totalWorkingMinutes / 60, 0);
                }
            }
            $daysWithEvents[$date->format('Y-m-d')] = [
                'day' => $date->format('d.m.'),
                'day_string' => $date->shortDayName,
                'full_day' => $date->format('d.m.Y'),
                'short_day' => $date->format('d.m'),
                'events' => $events,
                'plannedWorkingHours' => $plannedWorkingHours,
                'is_monday' => $date->isMonday(),
                'week_number' => $date->weekOfYear,
                'month_number' => $date->month,
            ];
            $totalPlannedWorkingHours += $plannedWorkingHours;
        }

        return [
            $daysWithEvents,
            $totalPlannedWorkingHours,
        ];
    }

    public function getEventsWhereServiceProviderHasShiftsFilteredByDateOfShifts(
        int $serviceProviderId,
        Carbon $date
    ): Collection {
        return $this->eventRepository
            ->getEventsWhereServiceProviderHasShifts($serviceProviderId)
            ->filter(
                function ($event) use ($date) {
                    return in_array($date->format('d.m.Y'), $event->getDaysOfShifts());
                }
            );
    }

    public function getShiftPlanDto(
        UserService $userService,
        FreelancerService $freelancerService,
        ServiceProviderService $serviceProviderService,
        RoomService $roomService,
        CraftService $craftService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        FilterService $filterService,
        ShiftFilterController $shiftFilterController,
        ShiftQualificationService $shiftQualificationService,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        AreaService $areaService,
        DayServicesService $dayServicesService
    ): ShiftPlanDto {
        [$startDate, $endDate] = $userService->getUserShiftCalendarFilterDatesOrDefault($userService->getAuthUser());

        $periodArray = [];
        foreach (($calendarPeriod = CarbonPeriod::create($startDate, $endDate)) as $period) {
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
                'add_week_separator' => $period->isSunday()
            ];
        }

        $events = $this->eventRepository->getEventsWhereHasShiftsStartAndEndTimeOverlap(
            $startDate,
            $endDate
        );
        $filteredRooms = $roomService->getFilteredRooms(
            $startDate,
            $endDate,
            $userService->getAuthUser()->shift_calendar_filter
        );

        return ShiftPlanDto::newInstance()
            ->setEvents($events)
            ->setHistory($this->getEventShiftsHistoryChanges($events))
            ->setCrafts($craftService->getAll())
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setProjects($projectService->getAll())
            ->setShiftPlan(
                $roomService->collectEventsForRoomsShift(
                    $filteredRooms,
                    $calendarPeriod,
                    null,
                    true
                )
            )
            ->setRooms($filteredRooms)
            ->setDays($periodArray)
            ->setFilterOptions(
                $filterService->getCalendarFilterDefinitions(
                    $roomCategoryService,
                    $roomAttributeService,
                    $eventTypeService,
                    $areaService,
                    $projectService,
                    $roomService
                )
            )
            ->setUserFilters($userService->getAuthUser()->shift_calendar_filter)
            ->setDateValue([$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->setPersonalFilters($shiftFilterController->index())
            ->setSelectedDate(null)
            ->setUsersForShifts(
                $userService->getUsersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    UserShiftPlanResource::class,
                    true
                )
            )
            ->setFreelancersForShifts(
                $freelancerService->getFreelancersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    FreelancerShiftPlanResource::class,
                    true
                )
            )
            ->setServiceProvidersForShifts(
                $serviceProviderService->getServiceProvidersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    ServiceProviderShiftPlanResource::class
                )
            )
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setDayServices($dayServicesService->getAll());
    }

    /**
     * @return array<int, mixed>
     */
    public function getEventShiftsHistoryChanges(Collection $events): array
    {
        return $events->flatMap(function ($event) {
            return $event->shifts->flatMap(function ($shift) {
                // Sort each shift's historyChanges by created_at in descending order
                $historyArray = [];
                foreach ($shift->historyChanges()->sortByDesc('created_at') as $history) {
                    $historyArray[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }

                return $historyArray;
            });
        })->all();
    }

    public function createEventManagementDto(
        CalendarService $calendarService,
        RoomService $roomService,
        UserService $userService,
        FilterService $filterService,
        FilterController $filterController,
        ProjectTabService $projectTabService,
        EventTypeService $eventTypeService,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        AreaService $areaService,
        ProjectService $projectService,
        ?CalendarFilter $calendarFilter,
        ?bool $atAGlance
    ): EventManagementDto {
        [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault($calendarFilter);

        $showCalendar = $calendarService->createCalendarData(
            $startDate,
            $endDate,
            $userService,
            $filterService,
            $filterController,
            $roomService,
            $roomCategoryService,
            $roomAttributeService,
            $eventTypeService,
            $areaService,
            $projectService,
            $calendarFilter,
        );

        return EventManagementDto::newInstance()
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setCalendar($showCalendar['roomsWithEvents'])
            ->setDays($showCalendar['days'])
            ->setDateValue($showCalendar['dateValue'])
            ->setCalendarType($showCalendar['calendarType'])
            ->setSelectedDate($showCalendar['selectedDate'])
            ->setEventsWithoutRoom($showCalendar['eventsWithoutRoom'])
            ->setEventsAtAGlance(
                $atAGlance ?
                    $calendarService->getEventsAtAGlance($startDate, $endDate) :
                    SupportCollection::make()
            )
            ->setRooms(
                $roomService->getFilteredRooms(
                    $startDate,
                    $endDate,
                    $userService->getAuthUser()->calendar_filter
                ),
            )
            ->setEvents(
                CalendarEventDto::newInstance()
                    ->setAreas($showCalendar['filterOptions']['areas'])
                    ->setProjects($showCalendar['filterOptions']['projects'])
                    ->setEventTypes($showCalendar['filterOptions']['eventTypes'])
                    ->setRoomCategories($showCalendar['filterOptions']['roomCategories'])
                    ->setRoomAttributes($showCalendar['filterOptions']['roomAttributes'])
                    ->setEvents(
                        $startDate->format('d.m.Y') === $endDate->format('d.m.Y') ?
                            SupportCollection::make(
                                CalendarEventResource::collection(
                                    $calendarService->getEventsOfInterval(
                                        $startDate,
                                        $endDate
                                    )
                                )->resolve()
                            ) :
                            SupportCollection::make()
                    )
            )
            ->setFilterOptions($showCalendar["filterOptions"],)
            ->setPersonalFilters($showCalendar['personalFilters'])
            ->setUserFilters($showCalendar['user_filters'])
            ->setFirstProjectTabId($projectTabService->findFirstProjectTab()?->id)
            ->setFirstProjectCalendarTabId($projectTabService->findFirstProjectTabWithCalendarComponent()?->id);
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
        $user
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

    public function save(Event $event): Event
    {
        return $this->eventRepository->save($event);
    }
}
