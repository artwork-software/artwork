<?php

namespace Artwork\Modules\Event\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Http\Controllers\ShiftFilterController;
use App\Http\Resources\MinimalShiftPlanShiftResource;
use App\Settings\EventSettings;
use App\Settings\ShiftSettings;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Services\CollectionService;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\DTO\CalendarHolidayDTO;
use Artwork\Modules\Calendar\DTO\CalendarPeriodDTO;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\Event\DTOs\EventManagementDto;
use Artwork\Modules\Event\DTOs\ShiftPlanDto;
use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Events\EventUpdated;
use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Events\RemoveEvent;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Event\Models\EventComment;
use Artwork\Modules\Event\Services\EventCommentService;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Services\EventPropertyService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Models\PresetShiftShiftsQualifications;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFilter;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Services\ShiftTimePresetService;
use Artwork\Modules\Event\Models\SubEvent;
use Artwork\Modules\Event\Services\SubEventService;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourService;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserShiftCalendarFilter;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

readonly class EventService
{
    private ?Collection $cachedData;
    public function __construct(
        private EventRepository $eventRepository,
        private WorkingHourService $workingHourService,
        private ShiftTimePresetService $shiftTimePresetService,
        private CollectionService $collectionService,
        private EventCollectionService $eventCollectionService,
        private EventTypeService $eventTypeService,
        private readonly AuthManager $authManager
    ) {
        $this->cachedData = null;
    }

    public function importShiftPreset(
        Event $event,
        ShiftPreset $shiftPreset,
        TimelineService $timelineService,
        ShiftService $shiftService,
        ShiftQualificationService $shiftQualificationService,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        //$timelineService->forceDeleteTimelines($event->timelines);
        /*foreach ($shiftPreset->timeline as $shiftPresetTimeline) {
            $timelineService->createFromShiftPresetTimeline($shiftPresetTimeline, $event);
        }*/

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

        if ($event->room_id){
            broadcast(new RemoveEvent($event, $event->room_id));
        }

        $event->verifications()->each(function (EventVerification $eventVerification) use ($event) {
            $eventVerification->delete();
        });

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
     * Formatiert Minuten in HH:MM Format.
     */
    private function formatTime(int $minutes): string {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * Summiert zwei Zeitangaben im Format HH:MM.
     */
    private function sumTimes(string $time1, string $time2): string {
        [$h1, $m1] = explode(':', $time1);
        [$h2, $m2] = explode(':', $time2);

        $totalMinutes = ($h1 * 60 + $m1) + ($h2 * 60 + $m2);
        return $this->formatTime($totalMinutes);
    }

    public function getDaysWithEventsAndTotalPlannedWorkingHours(
        int $modelId,
        string $modelType,
        Carbon $startDate,
        Carbon $endDate,
    ): array {
        $daysWithData = [];
        $calculatePlannedWorkingHours = function ($shifts): array {
            $earliestStart = null;
            $latestEnd = null;
            $totalBreakMinutes = 0;

            foreach ($shifts as $shift) {
                $start = Carbon::parse($shift['start']);
                $end = Carbon::parse($shift['end']);

                $earliestStart = ($earliestStart === null || $start->lt($earliestStart)) ? $start : $earliestStart;
                $latestEnd = ($latestEnd === null || $end->gt($latestEnd)) ? $end : $latestEnd;

                $totalBreakMinutes += $shift['break_minutes'];
            }

            $totalWorkMinutes = ($earliestStart !== null && $latestEnd !== null)
                ? max(($earliestStart->diffInMinutes($latestEnd) - $totalBreakMinutes), 0)
                : 0;

            return [
                'totalWorkTime' => $this->formatTime($totalWorkMinutes),
                'totalBreakTime' => $this->formatTime($totalBreakMinutes),
            ];
        };

        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $daysWithData[$formattedDate] = [
                'date' => $formattedDate,
                'shifts' => [],
                'totalWorkTime' => '00:00',
                'totalBreakTime' => '00:00',
            ];
        }

        $mapping = [
            'freelancer' => ['id' => 'freelancer_id', 'relation' => 'freelancer'],
            'service_provider' => ['id' => 'service_provider_id', 'relation' => 'serviceProvider'],
        ];

        $modelToFind = $mapping[$modelType]['id'] ?? 'user_id';
        $relationToFind = $mapping[$modelType]['relation'] ?? 'users';

        $events = Event::query()
            ->with(
                [
                    'room',
                    'shifts' => function (HasMany $query) use ($relationToFind, $modelToFind, $modelId): void {
                        $query->whereRelation($relationToFind, $modelToFind, $modelId);
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
                'shifts.' . $relationToFind,
                function (Builder $builder) use ($modelToFind, $modelId): void {
                    $builder->where($modelToFind, $modelId);
                }
            )
            ->whereBetween('start_time', $period)
            ->whereBetween('end_time', $period)
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get();


        foreach ($events as $event) {
            /** @var Shift $shift */
            foreach ($event['shifts'] as $shift) {
                $shiftDate = Carbon::parse($shift->start_date)->format('Y-m-d');

                $plannedData = $calculatePlannedWorkingHours([$shift]);

                /** @var Project $project */
                $project = $event->project;

                $daysWithData[$shiftDate]['shifts'][] = [
                    'room' => $shift->room,
                    'project' => $event->project,
                    'projectIsGroup' => $project->is_group,
                    'projectIsInGroup' => $project->groups->isNotEmpty(),
                    'projectGroups' => $project->groups->isNotEmpty() ? $project->groups : null,
                    'event' => $event,
                    'id' => $shift->id,
                    'name' => $shift->name ?? '',
                    'start' => $shift->start,
                    'end' => $shift->end,
                    'break_minutes' => $shift->break_minutes,
                    'description' => $shift->description,
                    'is_committed' => (bool) $shift->is_committed,
                    'craft' => $shift->craft ?? [],
                    'users' => $shift->users ?? [],
                    'freelancer' => $shift->freelancer ?? [],
                    'serviceProvider' => $shift->serviceProvider ?? [],
                    'shiftQualifications' => $shift->shiftsQualifications ?? [],
                    'plannedWorkingHours' => $plannedData['totalWorkTime'],
                ];

                $daysWithData[$shiftDate]['totalWorkTime'] = $this->sumTimes(
                    $daysWithData[$shiftDate]['totalWorkTime'],
                    $plannedData['totalWorkTime']
                );
                $daysWithData[$shiftDate]['totalBreakTime'] = $this->sumTimes(
                    $daysWithData[$shiftDate]['totalBreakTime'],
                    $plannedData['totalBreakTime']
                );
            }
        }

        $shifts = Shift::query()
            ->with(['room', 'users', 'users.dayServices', 'freelancer', 'serviceProvider', 'shiftsQualifications'])
            ->whereHas($relationToFind, function (Builder $builder) use ($modelToFind, $modelId): void {
                $builder->where($modelToFind, $modelId);
            })
            ->whereBetween('start_date', [$startDate, $endDate])
            ->whereBetween('end_date', [$startDate, $endDate])
            ->orderBy('start')
            ->get();

        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            if (!$shift->event_id) {
                $shiftDate = Carbon::parse($shift->start_date)->format('Y-m-d');
                $plannedData = $calculatePlannedWorkingHours([$shift]);

                $daysWithData[$shiftDate]['shifts'][] = [
                    'room' => $shift->room,
                    'project' => null,
                    'event' => null,
                    'id' => $shift->id,
                    'name' => $shift->name ?? '',
                    'start' => $shift->start,
                    'end' => $shift->end,
                    'break_minutes' => $shift->break_minutes,
                    'description' => $shift->description,
                    'is_committed' => (bool) $shift->is_committed,
                    'craft' => $shift->craft ?? [],
                    'users' => $shift->users ?? [],
                    'freelancer' => $shift->freelancer ?? [],
                    'serviceProvider' => $shift->serviceProvider ?? [],
                    'shiftQualifications' => $shift->shiftsQualifications ?? [],
                    'plannedWorkingHours' => $plannedData['totalWorkTime'],
                ];

                $daysWithData[$shiftDate]['totalWorkTime'] = $this->sumTimes(
                    $daysWithData[$shiftDate]['totalWorkTime'],
                    $plannedData['totalWorkTime']
                );
                $daysWithData[$shiftDate]['totalBreakTime'] = $this->sumTimes(
                    $daysWithData[$shiftDate]['totalBreakTime'],
                    $plannedData['totalBreakTime']
                );
            }
        }

        foreach ($daysWithData as &$day) {
            usort($day['shifts'], fn($a, $b) => strtotime($a['start']) - strtotime($b['start']));
        }

        Inertia::share([
            'daysWithData' => $daysWithData,
        ]);

        return $daysWithData;
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
        ProjectTabService $projectTabService
    ): ShiftPlanDto {
        [$startDate, $endDate] = $userService->getUserShiftCalendarFilterDatesOrDefault($user);

        $periodArray = $this->generatePeriodArray($startDate, $endDate, $user);
        $userFilter = $user->userFilters()->shiftCalendar()->first();
        $userCalendarSettings = $user->calendar_settings;
        $rooms = $this->fetchFilteredRooms($userFilter, $startDate, $endDate);

        $this->filterRoomsEventsAndShifts($rooms, $userFilter, $startDate, $endDate, $userCalendarSettings, true);

        $mappedRooms = $this->mapRoomsToContent($rooms, $startDate, $endDate);


        return $this->buildShiftPlanDto(
            $periodArray,
            $userService,
            $craftService,
            $filterService,
            $shiftFilterController,
            $freelancerService,
            $serviceProviderService,
            $shiftQualificationService,
            $dayServicesService,
            $projectTabService,
            $startDate,
            $endDate,
            $user,
            $mappedRooms
        );
    }

    public function generatePeriodArray($startDate, $endDate, User $user, bool $extraRow = true): array
    {
        $periodArray = [];

        if (!$startDate || !$endDate) {
            return $periodArray;
        }

        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);

        foreach ($calendarPeriod as $period) {
            if ($extraRow){
                if ($period->isMonday()) {
                    $periodArray[] = [
                        'isExtraRow' => true,
                        'weekNumber' => $period->weekOfYear,
                    ];
                }
            }

            $holidays = $this->getHolidaysForPeriod($period);

            $periodArray[] = [
                'day' => $period->format('d.m.'),
                'dayString' => $period->shortDayName,
                'isWeekend' => $period->isWeekend(),
                'fullDay' => $period->format('d.m.Y'),
                'shortDay' => $period->format('d.m'),
                'withoutFormat' => $period->format('Y-m-d'),
                'fullDayDisplay' => $period->format('d.m.y'),
                'weekNumber' => $period->weekOfYear,
                'isMonday' => $period->isMonday(),
                'monthNumber' => $period->month,
                'isSunday' => $period->isSunday(),
                'isFirstDayOfMonth' => $period->isSameDay($period->copy()->startOfMonth()),
                'addWeekSeparator' => $period->isSunday(),
                'holidays' => $holidays,
                'hoursOfDay' => $user->getAttribute('daily_view')
                    ? collect(range(0, 23))->map(function ($hour) {
                        return Carbon::createFromTime($hour)->format('H:i');
                    })->toArray()
                    : [],
            ];
        }

        return $periodArray;
    }



    public function getHolidaysForPeriod($period): SupportCollection
    {
        return Holiday::where(function (Builder $query) use ($period): void {
            $query->where(function (Builder $q) use ($period): void {
                $q->whereDate('date', '<=', $period->format('Y-m-d'))
                    ->whereDate('end_date', '>=', $period->format('Y-m-d'));
            })->orWhere(function (Builder $q) use ($period): void {
                $q->where('yearly', true)
                    ->whereMonth('date', $period->month)
                    ->whereDay('end_date', $period->day);
            });
        })->with('subdivisions')->get()->map(fn($holiday) => new CalendarHolidayDTO(
            name: $holiday->name,
            date: $holiday->date->format('Y-m-d'),
            end_date: $holiday->end_date->format('Y-m-d'),
            color: $holiday->color,
            subdivisions: $holiday->subdivisions->pluck('name')->toArray(),
        ));
    }

    public function fetchFilteredRooms(UserFilter $filter, $startDate, $endDate, UserCalendarSettings|null $userCalendarSettings = null)
    {
        $userCalendarFilter = $filter;

        return Room::query()->unlessRoomIds($userCalendarFilter?->rooms)
            ->unlessRoomAttributeIds($userCalendarFilter?->room_attributes)
            ->unlessAreaIds($userCalendarFilter?->areas)
            ->unlessRoomCategoryIds($userCalendarFilter?->room_categories)
            ->whenFilterAdjoiningWithStartAndEndDate(
                $userCalendarFilter?->adjoining_not_loud,
                $userCalendarFilter?->adjoining_no_audience,
                $startDate,
                $endDate
            )
            ->when($userCalendarSettings?->hide_unoccupied_rooms, function ($query) use ($startDate, $endDate) {
                $query->whereHas('events', function ($eventQuery) use ($startDate, $endDate) {
                    $eventQuery->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_time', [$startDate, $endDate])
                            ->orWhereBetween('end_time', [$startDate, $endDate])
                            ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                                $subQuery->where('start_time', '<', $startDate)
                                    ->where('end_time', '>', $endDate);
                            });
                    });
                });
            })
            ->orderBy('order')
            ->get();

    }

    public function filterRoomsEventsAndShifts(
        $rooms,
        UserFilter $filter,
        $startDate,
        $endDate,
        UserCalendarSettings $userCalendarSettings = null,
        $isShiftPlan = false
    ): void {
        $q = Event::query();
        $q->where(function(Builder $query) use ($startDate, $endDate) {
            $query->where(function(Builder $q) use ($startDate, $endDate) {
                $q->where('start_time', '>=', $startDate)
                    ->where('start_time', '<=', $endDate);
            })->orWhere(function(Builder $q)  use ($startDate, $endDate){
                $q->where('end_time', '>=', $startDate)
                    ->where('end_time', '<=', $endDate);
            })->orWhere(function(Builder $q) use ($startDate, $endDate) {
                $q->where('start_time', '<', $startDate)
                    ->where('end_time', '>', $endDate);
            });
        });

        if (!empty ($filter->event_types)) {
            $q->where('events.event_type_id', $filter->event_types);
        }

        $q->whereIn('room_id', $rooms->pluck('id'));
        $events = $q->get();

        foreach ($rooms as $room) {
            if ($isShiftPlan) {
                $shifts = $room->shifts->filter(function ($shift) use ($startDate, $endDate) {
                    /** @var Shift $shift */
                    return ($shift->start_date <= $endDate && $shift->end_date >= $startDate);
                });

                $room->shifts = $shifts;
            }

            $roomEvents = $events->filter(function ($event) use ($room) {
                return $event->room_id === $room->id;
            });

            $roomEvents = $roomEvents->map(function ($event) use ($isShiftPlan, $userCalendarSettings) {
                $startTime = Carbon::parse($event->start_time);
                $eventType = $this->eventTypeService->findById($event->event_type_id);
                //$creator = $event->creator;
                /** @var Project $project */
                $project = $event->project ?: null;
                $projectState = null;
                if($project?->state && $userCalendarSettings->project_status){
                    /** @var ProjectState $projectState */
                    $projectState = ProjectState::find($project->state);
                }
                $eventArray = [
                    'id' => $event->id,
                    'start' => $startTime,
                    'startTime' => Carbon::parse($event->start_time, 'Europe/Berlin')->format('Y-m-d H:i'),
                    'end' => Carbon::parse($event->end_time, 'Europe/Berlin')->format('Y-m-d H:i'),
                    'eventName' => $event->eventName,
                    'description' => $event->description,
                    'audience' => $event->audience,
                    'isLoud' => $event->is_loud,
                    'projectId' => $event->project_id,
                    'projectName' => $event->project?->name,
                    'eventTypeId' => $event->event_type_id,
                    'eventStatusId' => $event->event_status_id,
                    'eventStatusColor' => $event->eventStatus?->color,
                    'eventTypeName' => $eventType?->name,
                    'projectArtists' => $project?->artists,
                    'eventTypeAbbreviation' => $eventType?->abbreviation,
                    'eventTypeColor' => $eventType?->hex_code,
                    'created_at' => $event->created_at?->format('d.m.Y, H:i'),
                    'occupancy_option' => $event->occupancy_option,
                    'allDay' => $event->allDay,
                    'eventProperties' => $event->getAttribute('eventProperties'),
                    'eventTypeColorBackground' => ($eventType?->getAttribute('hex_code') ?? '#cccccc') . '33',
                    'event_type_color' => $eventType?->getAttribute('hex_code') ?? '#cccccc',
                    'eventType' => $eventType ? [
                        'id' => $eventType->id,
                        'name' => $eventType->name,
                        'hex_code' => $eventType->hex_code,
                        'abbreviation' => $eventType->abbreviation,
                    ] : null,
                    'days_of_event' => $event->days_of_event,
                    'option_string' => $event->option_string,
                    'formatted_dates' => $event->formatted_dates,
                    'formattedDates' => $event->formatted_dates,
                    'timesWithoutDates' => $event->timesWithoutDates,
                    'is_series' => $event->is_series,
                    'series' => $this->aggregateSeriesEvents($event),
                    'start_hour' => $event->getAttribute('start_hour') . ':00',
                    'event_length_in_hours' => $event->getAttribute('event_length_in_hours'),
                    'hours_to_next_day' => $event->getAttribute('hours_to_next_day'),
                    'minutes_form_start_hour_to_start' => $event->getAttribute('minutes_form_start_hour_to_start'),
                    'roomId' => $event->getAttribute('room_id'),
                    'roomName' => $event->getAttribute('room')?->getAttribute('name'),
                    'subEvents' => $event->getAttribute('subEvents'),
                    //'created_by' => $creator, // lazy load
                ];

                if ($userCalendarSettings->work_shifts || $isShiftPlan){
                    $eventArray['shifts'] = MinimalShiftPlanShiftResource::collection($event->shifts)->resolve();
                    $eventArray['days_of_shifts'] = $event->getDaysOfShifts($event->shifts);
                }

                if ($userCalendarSettings->project_status){
                    $eventArray['projectStatusId'] =  $projectState?->id;
                    $eventArray['projectStatusBackgroundColor'] =  $projectState?->color . '33';
                    $eventArray['projectStatusBorderColor'] =  $projectState?->color;
                    $eventArray['projectStatusName'] =  $projectState?->name;
                }

                return $eventArray;
            });
            $filterEventPropertyIds = $filter->getAttribute('event_properties') ?? [];

            $roomEvents = $roomEvents
                ->when(count($filterEventPropertyIds) > 0, function ($collection) use ($filterEventPropertyIds) {
                    return $collection->filter(function ($event) use ($filterEventPropertyIds) {
                        // Stelle sicher, dass eventProperties als Array vorhanden ist
                        $eventProperties = $event['eventProperties'] ?? [];

                        foreach ($eventProperties as $eventProperty) {
                            if (in_array($eventProperty['id'], $filterEventPropertyIds)) {
                                return true;
                            }
                        }
                        return false;
                    });
                });
            $room->events = $roomEvents;
        }
    }

    private function aggregateSeriesEvents($event): array
    {
        if (!($series = $event->getAttribute('series')) instanceof SeriesEvents) {
            return [];
        }

        return [
            'id' => $series->getAttribute('id'),
            'end_date' => $series->getAttribute('end_date')->format('Y-m-d'),
        ];
    }

    public function mapRoomsToContent(Collection $rooms, $startDate, $endDate, bool $withShifts = true): array
    {
        return $rooms->map(function ($room) use ($withShifts, $startDate, $endDate) {
            $content = $this->initializeContentArray($startDate, $endDate);

            if ($withShifts) {
                $shifts = $room->shifts;
                foreach ($shifts as $shift) {
                    $shiftDate = Carbon::parse($shift->start_date)->format('d.m.Y');
                    if (isset($content[$shiftDate])) {
                        $content[$shiftDate]['shifts'][] = $shift;
                    }
                }
            }
            foreach ($room->events as $event) {
                $eventDate = Carbon::parse($event['startTime'])->format('d.m.Y');
                if (isset($content[$eventDate])) {
                    $content[$eventDate]['events'][] = $event;
                }
            }

            return [
                'roomId' => $room->id,
                'roomName' => $room->name,
                'content' => $content,
            ];
        })->toArray();
    }

    public function mapRoomsToContentForCalendar(Collection $rooms, $startDate, $endDate)
    {
        return $rooms->map(function ($room) use ($startDate, $endDate) {
            $content = $this->initializeContentArray($startDate, $endDate);

            $shiftDays = $room->shifts->flatMap(function ($shift) {
                return collect($shift->getAttribute('days_of_shift'))->mapWithKeys(fn($date) => [$date => $shift]);
            });

            foreach ($shiftDays as $shiftDate => $shift) {
                if (isset($content[$shiftDate])) {
                    $content[$shiftDate]['shifts'][] = $shift;
                }
            }

            $eventDays = $room->events->flatMap(function ($event) {
                return collect($event['days_of_event'])->map(function ($date) use ($event) {
                    return [
                        'date' => $date,
                        'event' => $event,
                    ];
                });
            });

            foreach ($eventDays as $eventDay) {
                $eventDate = $eventDay['date'];
                $event = $eventDay['event'];

                if (isset($content[$eventDate])) {
                    $content[$eventDate]['events'][] = $event;
                }
            }

            return [
                'roomId' => $room->id,
                'roomName' => $room->name,
                'content' => $content,
            ];
        })->toArray();
    }

    public function initializeContentArray($startDate, $endDate): array
    {
        $content = [];

        if (!$startDate || !$endDate) {
            return $content;
        }

        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        foreach ($period as $date) {
            $formattedDate = $date->format('d.m.Y');
            $content[$formattedDate] = [
                'events' => [],
                'shifts' => [],
            ];
        }

        return $content;
    }

    public function buildShiftPlanDto(
        array $periodArray,
        UserService $userService,
        CraftService $craftService,
        FilterService $filterService,
        ShiftFilterController $shiftFilterController,
        FreelancerService $freelancerService,
        ServiceProviderService $serviceProviderService,
        ShiftQualificationService $shiftQualificationService,
        DayServicesService $dayServicesService,
        ProjectTabService $projectTabService,
        $startDate,
        $endDate,
        User $user,
        array $mappedRooms
    ): ShiftPlanDto {
        return ShiftPlanDto::newInstance()
            ->setHistory($this->getEventShiftsHistoryChanges())
            ->setCrafts(
                $craftService->getAll([
                    'managingUsers',
                    'managingFreelancers',
                    'managingServiceProviders'
                ])
            )
            ->setDays($periodArray)
            ->setFilterOptions($filterService->getCalendarFilterDefinitions())
            ->setUserFilters($userService->getAuthUser()->userFilters()->shiftFilter()->first())
            ->setDateValue([$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->setPersonalFilters($shiftFilterController->index())
            ->setUsersForShifts(
                $this->workingHourService->getUsersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    UserShiftPlanResource::class,
                    true,
                    $user
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
            ->setCurrentUserCrafts(
                $userService->getAuthUserCrafts()->merge($craftService->getAssignableByAllCrafts())
            )
            ->setShiftTimePresets($this->shiftTimePresetService->getAll())
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
                )
            )
            ->setUseFirstNameForSort((new ShiftSettings())->use_first_name_for_sort)
            ->setUserShiftPlanShiftQualificationFilters($user->getAttribute('show_qualifications'))
            ->setMappedRooms($mappedRooms);
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
        ProjectCreateSettings $projectCreateSettings,
        EventPropertyService $eventPropertyService,
        ?Project $project = null,
    ): EventManagementDto {
        $user = $userService->getAuthUser();
        $userCalendarFilter = $user->userFilters()->calendarFilter()->first();
        $userCalendarSettings = $user->getAttribute('calendar_settings');

        //today is used if project calendar is opened and no events are given as project calendar
        //do not rely on user calendar filter dates
        $today = Carbon::now();

        if (
            !($useProjectTimePeriod = $userCalendarSettings->getAttribute('use_project_time_period')) &&
            !$project
        ) {
            [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault($userCalendarFilter);
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
            ->setUserFilters($user->userFilters()->calendarFilter()->first())
            ->setFirstProjectTabId($projectTabService->getFirstProjectTabId())
            ->setFirstProjectCalendarTabId(
                $projectTabService
                    ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR)
            )
            ->setFirstProjectShiftTabId(
                $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            )
            ->setShowArtists($projectCreateSettings->show_artists)
            ->setEventProperties($eventPropertyService->getAll());

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
        ProjectCreateSettings $projectCreateSettings,
        EventPropertyService $eventPropertyService,
        ?Project $project = null,
    ): EventManagementDto {
        $user = $userService->getAuthUser();
        $userCalendarFilter = $user->userFilters()->calendarFilter()->first();
        $userCalendarSettings = $user->getAttribute('calendar_settings');

        //today is used if project calendar is opened and no events are given as project calendar
        //do not rely on user calendar filter dates
        $today = Carbon::now();

        if (
            !($useProjectTimePeriod = $userCalendarSettings->getAttribute('use_project_time_period')) &&
            !$project
        ) {
            [$startDate, $endDate] = $userService->getUserCalendarFilterDatesOrDefault($userCalendarFilter);
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


        $periodArray = $this->generatePeriodArray(
            $startDate,
            $endDate,
            $user,
            false
        );

        if (!$startDate && !$endDate) {
            $calendarPeriod = [];
        } else {
            $calendarPeriod = CarbonPeriod::create($startDate, $endDate);
        }

        $months = [];
        foreach ($calendarPeriod as $period) {
            $month = $period->format('m.Y');
            if (!array_key_exists($month, $months)) {
                $months[$month] = [
                    'first_day_in_period' => $period->format('Y-m-d'),
                    'month' => $period->monthName,
                    'year' => $period->format('y'),
                ];
            }
        }
        $userFilter = $user->userFilters()->calendarFilter()->first();
        $rooms = $this->fetchFilteredRooms($userFilter, $startDate, $endDate, $userCalendarSettings);

        $this->filterRoomsEventsAndShifts($rooms, $userFilter, $startDate, $endDate, $userCalendarSettings);

        $mappedRooms = $this->mapRoomsToContentForCalendar($rooms, $startDate, $endDate);

        if ($useProjectTimePeriod && !$startDate && !$endDate) {
            $startDate = $today->startOfDay();
            $endDate = $today->endOfDay();
        }

        $dateValue = [
            $startDate ? $startDate->format('Y-m-d') : null,
            $endDate ? $endDate->format('Y-m-d') : null
        ];

        $calendarType = ($startDate && $endDate && $startDate->format('d.m.Y') === $endDate->format('d.m.Y')) ?
            'daily' :
            'individual';

        $selectedDate = ($startDate && $endDate && $startDate->format('Y-m-d') === $endDate->format('Y-m-d')) ?
            $startDate->format('Y-m-d') :
            null;

        $eventManagementDto = EventManagementDto::newInstance()
            //->setEventStatuses(EventStatus::orderBy('order')->get())
            //->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setCalendar($mappedRooms)
            ->setDays($periodArray)
            ->setMonths($months)
            ->setDateValue($dateValue)
            ->setCalendarType($calendarType)
            ->setSelectedDate($selectedDate)
            ->setEventsWithoutRoom( CalendarEventResource::collection(
                $this->eventCollectionService->getEventsWithoutRoom(
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
            )->resolve())
            ->setRooms($rooms)
            ->setAreas($areaService->getAll())
            ->setFilterOptions($filterService->getCalendarFilterDefinitions())
            ->setPersonalFilters($filterService->getPersonalFilter())
            ->setUserFilters($userService->getAuthUser()->userFilters()->calendarFilter()->first())
            ->setFirstProjectTabId($projectTabService->getFirstProjectTabId())
            ->setFirstProjectCalendarTabId(
                $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR)
            )
            ->setFirstProjectShiftTabId(
                $projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            )
            ->setShowArtists($projectCreateSettings->show_artists)
            ->setEventProperties($eventPropertyService->getAll()->map(
                function (EventProperty $eventProperty) {
                    return [
                        'id' => $eventProperty->getAttribute('id'),
                        'name' => $eventProperty->getAttribute('name'),
                        'icon' => $eventProperty->getAttribute('icon')
                    ];
                }
            ));


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

        /*if ($originalStartTime && $originalEndTime) {
            broadcast(
                new EventUpdated(
                    $event->getAttribute('room_id') ?? $originalRoomId,
                    $originalStartTime,
                    $originalEndTime
                )
            )->toOthers();
        }

        broadcast(new EventUpdated(
            $event->getAttribute('room_id') ?? $originalRoomId,
            $event->start_time,
            $event->is_series ?
                $event->series->end_date :
            $event->end_time
        ))->toOthers();*/

        broadcast(new EventCreated(
            $event->load(['event_type', 'project']),
            $event->room_id
        ));
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

    /**
     * @param Carbon $day
     * @param string|null $startTime
     * @param string|null $endTime
     * @return array{Carbon, Carbon, bool}
     */
    public function processEventTimesForTimeline(Carbon $day, ?string $startTime, ?string $endTime): array
    {
        $endDay = clone $day;
        $allDay = false;

        if (!$startTime && !$endTime) {
            $allDay = true;
            $day->startOfDay();
            $endDay->endOfDay();
        } else {
            $startTime = $startTime ? Carbon::parse($startTime) : null;
            $endTime = $endTime ? Carbon::parse($endTime) : null;

            // Wenn Startzeit oder Endzeit leer ist, beides gleichsetzen
            if (!$startTime && $endTime) {
                $startTime = clone $endTime;
            } elseif (!$endTime && $startTime) {
                $endTime = clone $startTime;
            }

            // Wenn beide Zeiten vorhanden sind
            if ($startTime && $endTime) {
                if ($endTime->lt($startTime)) {
                    // Wenn Endzeit vor Startzeit liegt, nächsten Tag für Endzeit setzen
                    $endDay->addDay();
                }
            }

            // Setze Start- und Endzeiten im Tag-Objekt
            if ($startTime) {
                $day->setTimeFromTimeString($startTime->toTimeString());
            }
            if ($endTime) {
                $endDay->setTimeFromTimeString($endTime->toTimeString());
            }
        }

        return [$day, $endDay, $allDay];
    }

    /**
     * Robust parsing for day input coming from UI which may be in formats like 'Y-m-d', 'd.m.Y', or 'd.m.y'.
     */
    private function parseDayInput(Carbon|string $day): Carbon
    {
        if ($day instanceof Carbon) {
            return $day->copy();
        }
        $day = trim($day);
        // Common explicit formats first
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            return Carbon::createFromFormat('Y-m-d', $day);
        }
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $day)) {
            return Carbon::createFromFormat('d.m.Y', $day);
        }
        if (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $day)) {
            // two-digit year, PHP maps 00-69 => 2000-2069, 70-99 => 1970-1999
            return Carbon::createFromFormat('d.m.y', $day);
        }
        // Fallback to Carbon::parse (still handles many ISO strings)
        return Carbon::parse($day);
    }


    public function createBulkEvent(
        array $event,
        Project $project,
        int $userId,
    ): Event {

        // Handle null or missing day input by using current date as fallback
        $dayInput = $event['day'] ?? null;
        if ($dayInput === null || $dayInput === '') {
            $day = Carbon::now()->startOfDay();
        } else {
            $day = $this->parseDayInput($dayInput);
        }
        [$startTime, $endTime, $allDay] = $this->processEventTimes(
            $day,
            $event['start_time'] ?? null,
            $event['end_time'] ?? null
        );
        // Respect explicit end_day from UI if provided (multi-day)
        if (!empty($event['end_day'])) {
            $explicitEndDay = $this->parseDayInput($event['end_day']);
            if ($allDay) {
                $endTime = $explicitEndDay->copy()->endOfDay();
            } else {
                // keep the provided end time (or the computed one) but on the explicit end date
                $endAt = ($event['end_time'] ?? null) ? \Carbon\Carbon::parse($event['end_time']) : $endTime;
                $endTime = $explicitEndDay->copy()->setTimeFromTimeString($endAt->toTimeString());
            }
        }
        /** @var Event $createdEvent */
        $createdEvent  = $project->events()->create([
            'eventName' => $event['name'] ?? '',
            'name' => $event['name'] ?? '',
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'allDay' => $allDay,
            'event_type_id' => $event['type']['id'],
            'room_id' => $event['room']['id'],
            'is_planning' => $event['is_planning'] ?? false,
        ]);

        $eventStatusSetting = app(EventSettings::class);

        if ($eventStatusSetting->enable_status) {
            if (isset($event['status']['id'])) {
                // Use provided status
                $createdEvent->update([
                    'event_status_id' => $event['status']['id']
                ]);
            } else {
                // Use default status if no status provided
                $defaultStatus = EventStatus::where('default', true)->first();
                if ($defaultStatus) {
                    $createdEvent->update([
                        'event_status_id' => $defaultStatus->id
                    ]);
                }
            }
        }

        return $createdEvent;
    }

    public function updateBulkEvent(
        SupportCollection $data,
        Event $event,
    ): void {
        // Day may be omitted from the UI payload when only times are changed.
        // In that case, fall back to the event's existing start date to avoid TypeError
        // when parseDayInput receives null.
        $dayInput = $data['day'] ?? null;
        if ($dayInput === null || $dayInput === '') {
            $day = Carbon::parse($event->start_time)->copy()->startOfDay();
        } else {
            $day = $this->parseDayInput($dayInput);
        }
        [$startTime, $endTime, $allDay] = $this->processEventTimes(
            $day,
            $data['start_time'] ?? null,
            $data['end_time'] ?? null
        );
        // Respect explicit end_day from UI if provided (multi-day update)
        if (!empty($data['end_day'])) {
            $explicitEndDay = $this->parseDayInput($data['end_day']);
            if ($allDay) {
                $endTime = $explicitEndDay->copy()->endOfDay();
            } else {
                $endAt = ($data['end_time'] ?? null) ? \Carbon\Carbon::parse($data['end_time']) : $endTime;
                $endTime = $explicitEndDay->copy()->setTimeFromTimeString($endAt->toTimeString());
            }
        }

        $this->eventRepository->update($event, [
            'eventName' => $data['name'],
            'name' => $data['name'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'allDay' => $allDay,
            'event_type_id' => $data['type']['id'],
            'room_id' => $data['room']['id'],
            'is_planning' => $data['is_planning'] ?? $event->is_planning,
        ]);


        // verschiebe schichten wenn event verschoben wird
        $shifts = $event->shifts;
        foreach ($shifts as $shift) {
            $shiftStart = Carbon::parse($shift->start_date);
            $shiftEnd = Carbon::parse($shift->end_date);
            $shiftDurationInDays = $shiftStart->diffInDays($shiftEnd);
            $shift->update([
                'start_date' => $startTime->format('Y-m-d'),
                'end_date' => $startTime->copy()->addDays($shiftDurationInDays)->format('Y-m-d')
            ]);
        }


        $eventStatusSetting = app(EventSettings::class);

        if ($eventStatusSetting->enable_status && isset($data['status']['id'])) {
            $this->eventRepository->update($event, [
                'event_status_id' => $data['status']['id']
            ]);
        }
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



    public function attachEventProperty(Event $event, EventProperty $eventProperty): Event
    {
        return $this->eventRepository->attachEventProperty($event, $eventProperty);
    }

    public function bulkMultiEditEvent(SupportCollection $eventIds, array $data): void
    {
        $updates = array_filter([
            'room_id' => $data['selectedRoom']['id'] ?? null,
            'event_type_id' => $data['selectedEventType']['id'] ?? null,
            'event_status_id' => $data['selectedEventStatus']['id'] ?? null,
            'name' => $data['eventName'] ?? null,
            'eventName' => $data['eventName'] ?? null,
            'is_planning' => $data['is_planning'] ?? null,
        ]);

        $selectedDay = $data['selectedDay'] ?? null;
        $selectedStartTime = $data['selectedStartTime'] ?? null;
        $selectedEndTime = $data['selectedEndTime'] ?? null;

        $this->eventRepository->updateEvents($eventIds, $updates, $selectedDay, $selectedStartTime, $selectedEndTime);
    }

    public function bulkDeleteEvent(SupportCollection $eventIds): void
    {
        if ($eventIds->isEmpty()) {
            return;
        }

        $this->eventRepository->deleteEvents($eventIds);
    }

}
