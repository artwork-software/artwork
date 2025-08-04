<?php

namespace App\Http\Controllers;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use App\Settings\ShiftSettings;
use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Casts\TimeAgoCast;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\MainPositionService;
use Artwork\Modules\Budget\Services\TableService;
use Artwork\Modules\Budget\Services\BudgetColumnSettingService;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\EventWithoutRoomDTO;
use Artwork\Modules\Calendar\DTO\ProjectDTO;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Calendar\Services\EventCalendarService;
use Artwork\Modules\Calendar\Services\EventPlanningCalendarService;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Events\EventDeleted;
use Artwork\Modules\Event\Events\EventUpdated;
use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Http\Requests\EventBulkCreateRequest;
use Artwork\Modules\Event\Http\Requests\EventStoreRequest;
use Artwork\Modules\Event\Http\Requests\EventUpdateRequest;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Http\Resources\EventShowResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Event\Services\EventCollisionService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventCommentService;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Services\EventPropertyService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\GlobalNotification\Services\GlobalNotificationService;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Shift\Services\ShiftPresetService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Services\ShiftTimePresetService;
use Artwork\Modules\Event\Services\SubEventService;
use Artwork\Modules\Task\Http\Resources\TaskDashboardResource;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Throwable;

class EventController extends Controller
{
    public function __construct(
        private readonly EventCollisionService $collisionService,
        private readonly NotificationService $notificationService,
        private readonly BudgetService $budgetService,
        private readonly EventService $eventService,
        private readonly ShiftService $shiftService,
        private readonly TimelineService $timelineService,
        private readonly ProjectTabService $projectTabService,
        private readonly ChangeService $changeService,
        private readonly SchedulingService $schedulingService,
        private readonly CraftInventoryItemEventService $craftInventoryItemEventService,
        private readonly RoomService $roomService,
        private readonly AuthManager $authManager,
        private readonly Redirector $redirector,
        private readonly EventCollectionService $eventCollectionService,
        private readonly EventCalendarService $eventCalendarService,
        private readonly CalendarDataService $calendarDataService,
        private readonly FilterService $filterService,
        private readonly AreaService $areaService,
        private readonly ShiftCalendarService $shiftCalendarService,
        private readonly CraftService $craftService,
        private readonly ShiftQualificationService $shiftQualificationService,
        private readonly DayServicesService $dayServicesService,
        private readonly FreelancerService $freelancerService,
        private readonly ServiceProviderService $serviceProviderService,
        private readonly WorkingHourService $workingHourService,
        private readonly UserService $userService,
        private readonly ShiftTimePresetService $shiftTimePresetService,
        private readonly ProjectService $projectService,
        private readonly EventPlanningCalendarService $eventPlanningCalendarService,
    ) {
    }

    public function getEventsForRoomsByDaysAndProject(
        Request $request,
        ProjectService $projectService
    ): JsonResponse {
        $desiredRoomIds = $request->collect('rooms')->all();
        $desiredDays = $request->collect('days')->all();
        $projectId = $request->get('projectId', 0);

        return new JsonResponse(
            [
                'roomData' => empty($desiredRoomIds) || empty($desiredDays) ?
                    [] :
                    $this->eventCollectionService->collectEventsForRoomsOnSpecificDays(
                        $desiredRoomIds,
                        $desiredDays,
                        $request->user()->userFilters()->calendarFilter()->first(),
                        $projectId > 0 ?
                            $projectService->findById($projectId) :
                            null
                    ),
                'eventsWithoutRoom' => !$request->boolean('reloadEventsWithoutRoom') ?
                    [] :
                    CalendarEventResource::collection(
                        $this->eventCollectionService->getEventsWithoutRoom(
                            $projectId,
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
                    )->resolve()
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function viewEventIndex(Request $request, ?Project $project = null): Response|JsonResponse
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $userCalendarFilter = $user->userFilters()->calendarFilter()->first(); //$user->getAttribute('calendar_filter');

        //dd($userCalendarFilter);


        $userCalendarSettings = $user->getAttribute('calendar_settings');
        $isPlanning = $request->boolean('isPlanning', false);


        $this->userService->shareCalendarAbo('calendar');
        $dateRangeRequested = false;

        if ($request->input('start_date') && $request->input('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $dateRangeRequested = true;
        } else {
            [$startDate, $endDate] = $this->calendarDataService
                ->getCalendarDateRange($userCalendarSettings, $userCalendarFilter, $project);
        }

        $calendarWarningText = '';

        if ($user->daily_view && $startDate->diffInDays($endDate) > 7) {
            $endDate = $startDate->copy()->addDays(7);
            $calendarWarningText = __('calendar.daily_view_info');
            $user->userFilters()->updateOrCreate([
                'filter_type' => UserFilterTypes::CALENDAR_FILTER->value
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }

        if ($startDate->diffInDays($endDate) > (365 * 2)) {
            $endDate = $startDate->copy()->addYears(2);
            $calendarWarningText = __('calendar.calendar_limit_two_years');
            $user->userFilters()->updateOrCreate([
                'filter_type' => UserFilterTypes::CALENDAR_FILTER->value
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }

        $period = $this->calendarDataService->createCalendarPeriodDto(
            $startDate,
            $endDate,
            $user,
            false
        );

        $months = [];
        foreach ($period as $periodObject) {
            $date = Carbon::parse($periodObject->withoutFormat);
            $month = $date->format('m.Y');
            if (!array_key_exists($month, $months)) {
                $months[$month] = [
                    'first_day_in_period' => $date->format('Y-m-d'),
                    'month' => $date->monthName,
                    'year' => $date->format('y'),
                ];
            }
        }

        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $startDate,
            $endDate,
        );

        if ($dateRangeRequested) {
            if ($isPlanning) {
                $this->eventPlanningCalendarService->filterRoomsEvents(
                    $rooms,
                    $userCalendarFilter,
                    $startDate,
                    $endDate,
                    $userCalendarSettings
                );
            } else {
                $this->eventCalendarService->filterRoomsEvents(
                    $rooms,
                    $userCalendarFilter,
                    $startDate,
                    $endDate,
                    $userCalendarSettings
                );
            }
        } else {
            if ($isPlanning) {
                $this->eventPlanningCalendarService->filterRoomsEvents(
                    $rooms,
                    $userCalendarFilter,
                    $startDate,
                    $endDate,
                    $userCalendarSettings
                );
            } else {
                $this->eventCalendarService->filterRoomsEventsWithMinimalData(
                    $rooms,
                    $userCalendarFilter,
                    $startDate,
                    $endDate,
                    $userCalendarSettings
                );
            }
        }

        if ($isPlanning) {
            $calendarData = $this->eventPlanningCalendarService->mapRoomsToContentForCalendar(
                $rooms,
                $startDate,
                $endDate,
            );
        } else {
            $calendarData = $this->eventCalendarService->mapRoomsToContentForCalendar(
                $rooms,
                $startDate,
                $endDate,
            );
        }

        $dateValue = [
            $startDate ? $startDate->format('Y-m-d') : null,
            $endDate ? $endDate->format('Y-m-d') : null
        ];

        $eventTypes = EventType::select(['id', 'name', 'abbreviation', 'hex_code'])
            ->get()
            ->keyBy('id');

        if ($dateRangeRequested) {
            return response()->json(['calendar' => $calendarData->rooms]);
        }

        return Inertia::render('Calendar/Index', [
            'period' => $period,
            'rooms' => $rooms,
            'calendar' => Inertia::always(fn() => $calendarData->rooms),
            'personalFilters' => Inertia::always(fn() =>
                $this->filterService->getPersonalFilter($user, UserFilterTypes::CALENDAR_FILTER->value)),
            'filterOptions' => $this->filterService->getCalendarFilterDefinitions(),
            'eventsWithoutRoom' => Event::query()->hasNoRoom()->get()->map(fn($event) =>
                EventWithoutRoomDTO::formModel($event, $userCalendarSettings, $eventTypes)),
            'areas' => $this->areaService->getAll(),
            'dateValue' => $dateValue,
            'user_filters' => $userCalendarFilter,
            'eventTypes' => EventType::all(),
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'event_properties' => EventProperty::all(),
            'first_project_tab_id' => $this->projectTabService->getDefaultOrFirstProjectTabId(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'first_project_shift_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),
            'projectNameUsedForProjectTimePeriod' => $userCalendarSettings->getAttribute('time_period_project_id') ?
                $this->projectService->findById(
                    $userCalendarSettings->getAttribute('time_period_project_id')
                )->name : null,
            'calendarWarningText' => $calendarWarningText,
            'months' => $months,
        ]);
    }

    public function viewPlanningCalendar(Request $request, ?Project $project = null): Response
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $userCalendarFilter = $user->userFilters()->planningCalendarFilter()->first();
        $userCalendarSettings = $user->getAttribute('calendar_settings');

        $this->userService->shareCalendarAbo('calendar');

        [$startDate, $endDate] = $this->calendarDataService
                ->getCalendarDateRange($userCalendarSettings, $userCalendarFilter, $project);

        $calendarWarningText = '';

        if ($user->daily_view && $startDate->diffInDays($endDate) > 7) {
            $endDate = $startDate->copy()->addDays(7);
            $calendarWarningText = __('calendar.daily_view_info');
            $user->userFilters()->updateOrCreate([
                'filter_type' => UserFilterTypes::PLANNING_FILTER->value
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }


        if ($startDate->diffInDays($endDate) > (365 * 2)) {
            $endDate = $startDate->copy()->addYears(2);
            $calendarWarningText = __('calendar.calendar_limit_two_years');
            $user->userFilters()->updateOrCreate([
                'filter_type' => UserFilterTypes::PLANNING_FILTER->value
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }

        $period = $this->calendarDataService->createCalendarPeriodDto(
            $startDate,
            $endDate,
            $user,
            false
        );

        $months = [];
        foreach ($period as $periodObject) {
            $date = Carbon::parse($periodObject->withoutFormat);
            $month = $date->format('m.Y');
            if (!array_key_exists($month, $months)) {
                $months[$month] = [
                    'first_day_in_period' => $date->format('Y-m-d'),
                    'month' => $date->monthName,
                    'year' => $date->format('y'),
                ];
            }
        }


        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $startDate,
            $endDate,
        );

        $this->eventPlanningCalendarService->filterRoomsEvents(
            $rooms,
            $userCalendarFilter,
            $startDate,
            $endDate,
            $userCalendarSettings
        );


        $calendarData = $this->eventPlanningCalendarService->mapRoomsToContentForCalendar(
            $rooms,
            $startDate,
            $endDate,
        );


        $dateValue = [
            $startDate ? $startDate->format('Y-m-d') : null,
            $endDate ? $endDate->format('Y-m-d') : null
        ];

        $eventTypes = EventType::select(['id', 'name', 'abbreviation', 'hex_code'])
            ->get()
            ->keyBy('id');


        //dd($this->filterService->getCalendarFilterDefinitions());

        return Inertia::render('PlanningCalendar/Index', [
            'period' => $period,
            'rooms' => $rooms,
            'calendar' => Inertia::always(fn() => $calendarData->rooms),
            'personalFilters' => Inertia::always(fn() => $this->filterService
                ->getPersonalFilter($user, UserFilterTypes::PLANNING_FILTER->value)),
            'filterOptions' => $this->filterService->getCalendarFilterDefinitions(),
            'eventsWithoutRoom' => Event::query()->hasNoRoom()->get()->map(fn($event) =>
                EventWithoutRoomDTO::formModel($event, $userCalendarSettings, $eventTypes)),
            'areas' => $this->areaService->getAll(),
            'dateValue' => $dateValue,
            'user_filters' => $userCalendarFilter,
            'eventTypes' => EventType::all(),
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'event_properties' => EventProperty::all(),
            'first_project_tab_id' => $this->projectTabService->getDefaultOrFirstProjectTabId(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'first_project_shift_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),
            'projectNameUsedForProjectTimePeriod' => $userCalendarSettings->getAttribute('time_period_project_id') ?
                $this->projectService->findById(
                    $userCalendarSettings->getAttribute('time_period_project_id')
                )->name : null,
            'calendarWarningText' => $calendarWarningText,
            'months' => $months,
            'verifierForEventTypIds' => $user->verifiableEventTypes->pluck('id'),
        ]);
    }

    public function viewShiftPlan(?Project $project = null): Response
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $userCalendarFilter = $user->userFilters()->shiftFilter()->first();
        $userCalendarSettings = $user->getAttribute('calendar_settings');
        $renderViewName = 'Shifts/ShiftPlan';
        $this->userService->shareCalendarAbo('shiftCalendar');


        [$startDate, $endDate] = $this->calendarDataService
            ->getCalendarDateRange($userCalendarSettings, $userCalendarFilter, $project);
        $calendarWarningText = '';
        if ($user->getAttribute('daily_view') && $startDate->diffInDays($endDate) > 7) {
            $endDate = $startDate->copy()->addDays(7);
            $calendarWarningText = __('calendar.daily_view_info');
            $user->userFilters()->updateOrCreate([
                'filter_type' => UserFilterTypes::SHIFT_FILTER->value
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }

        // only allow one month in shift plan view
        if ($startDate->diffInDays($endDate) > 31) {
            $endDate = $startDate->copy()->addDays(30);
            $calendarWarningText = __('calendar.calendar_limit_one_month');
            $user->userFilters()->updateOrCreate([
                'filter_type' => UserFilterTypes::SHIFT_FILTER->value
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }

        if ($user->getAttribute('daily_view')) {
            $renderViewName = 'Shifts/ShiftPlanDailyView';
        }

        $period = $this->calendarDataService->createCalendarPeriodDto(
            $startDate,
            $endDate,
            $user,
        );

        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $startDate,
            $endDate,
        );

        $this->shiftCalendarService->filterRoomsEventsAnShifts(
            $rooms,
            $userCalendarFilter,
            $startDate,
            $endDate,
            $userCalendarSettings,
            $user->getAttribute('daily_view')
        );


        $calendarData = $this->shiftCalendarService->mapRoomsToContentForCalendar(
            $rooms,
            $startDate,
            $endDate,
        );


        $dateValue = [
            $startDate ? $startDate->format('Y-m-d') : null,
            $endDate ? $endDate->format('Y-m-d') : null
        ];



        return Inertia::render($renderViewName, [
            'history' => $this->shiftCalendarService->getEventShiftsHistoryChanges(),
            'crafts' => $this->craftService->getAll([
                'managingUsers',
                'managingFreelancers',
                'managingServiceProviders',
                'users', 'freelancers', 'serviceProviders'
            ]),
            'rooms' => $rooms,
            'eventTypes' => EventType::all(),
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'event_properties' => EventProperty::all(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'days' => $period,
            'shiftPlan' => $calendarData->rooms,
            'personalFilters' => $this->filterService->getPersonalFilter($user, UserFilterTypes::SHIFT_FILTER->value),
            'filterOptions' => $this->filterService->getCalendarFilterDefinitions(),
            'dateValue' => $dateValue,
            'user_filters' => $userCalendarFilter,
            'shiftQualifications' => $this->shiftQualificationService->getAllOrderedByCreationDateAscending(),
            'dayServices' => $this->dayServicesService->getAll(),
            'firstProjectShiftTabId' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),
            'projectNameUsedForProjectTimePeriod' => $userCalendarSettings->getAttribute('time_period_project_id') ?
                $this->projectService->findById(
                    $userCalendarSettings->getAttribute('time_period_project_id')
                )->name : null,
            'shiftPlanWorkerSortEnums' => array_map(
                static function (ShiftPlanWorkerSortEnum $enum): string {
                    return $enum->name;
                },
                ShiftPlanWorkerSortEnum::cases()
            ),
            'useFirstNameForSort' => (new ShiftSettings())->use_first_name_for_sort,
            'userShiftPlanShiftQualificationFilters' => $user->getAttribute('show_qualifications'),
            'freelancersForShifts' => $this->freelancerService->getFreelancersWithPlannedWorkingHours(
                $startDate,
                $endDate,
                FreelancerShiftPlanResource::class,
                true,
                $user
            ),
            'serviceProvidersForShifts' => $this->serviceProviderService->getServiceProvidersWithPlannedWorkingHours(
                $startDate,
                $endDate,
                ServiceProviderShiftPlanResource::class,
                $user
            ),
            'usersForShifts' => $this->workingHourService->getUsersWithPlannedWorkingHours(
                $startDate,
                $endDate,
                UserShiftPlanResource::class,
                true
            ),
            'currentUserCrafts' => $this->userService->getAuthUserCrafts()->merge(
                $this->craftService->getAssignableByAllCrafts()
            ),
            'shiftTimePresets' => $this->shiftTimePresetService->getAll(),
            'calendarWarningText' => $calendarWarningText,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     * @throws Throwable
     */
    public function getEventsForRoomsByDaysWithUser(
        Request $request,
        ShiftWorkerService $shiftWorkerService,
        UserService $userService
    ): array {
        return [
            'roomData' => $this->roomService->collectEventsForRoomsShiftOnSpecificDays(
                $this->roomService,
                $userService,
                $request->collect('rooms')->all(),
                $request->collect('days')->all(),
                $userService->getAuthUser()?->userFilters()->shiftFilter()->first()
            ),
            'workerData' => $shiftWorkerService
                ->getResolvedWorkerShiftPlanResourcesByIdsAndTypesWithPlannedWorkingHours(
                    $request->collect('workers')->all()
                )
        ];
    }

    public function getEventsForRoomsByDaysWithoutUser(
        Request $request,
        UserService $userService
    ): array {
        return [
            'roomData' => $this->roomService->collectEventsForRoomsShiftOnSpecificDays(
                $this->roomService,
                $userService,
                $request->collect('rooms')->all(),
                $request->collect('days')->all(),
                $userService->getAuthUser()?->userFilters()->shiftFilter()->first()
            ),
        ];
    }

    //@todo: fix phpcs error - fix complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function showDashboardPage(
        GlobalNotificationService $globalNotificationService,
        CarbonService $carbonService,
        EventPropertyService $eventPropertyService
    ): Response {
        $event = null;
        $tasks = Task::query()
            ->where('done', false)
            ->where(function ($query): void {
                $query->whereHas('checklist', function (Builder $checklistBuilder): void {
                    $checklistBuilder->where('user_id', Auth::id());
                })
                    ->orWhereHas('task_users', function (Builder $userBuilder): void {
                        $userBuilder->where('user_id', Auth::id());
                    });
            })
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline ASC')
            ->limit(5)
            ->get();

        $user = Auth::user();

        $now = $carbonService->getNow();
        $shiftsOfDay = $user
            ->shifts()
            ->whereDate(
                'shifts.start_date',
                $now->format('Y-m-d')
            )->with(['event','event.project','event.room', 'event.event_type'])->get();

        // get user events from Projects in which the user is currently working
        $userEvents = Event::where('start_time', '>=', Carbon::now()->startOfDay())
            ->where('start_time', '<=', Carbon::now()->endOfDay())
            ->whereHas(
                'project',
                function (Builder $query): void {
                    $query->whereHas('users', function (Builder $query): void {
                        $query->where('user_id', Auth::id());
                    });
                }
            )->with(['project', 'room', 'event_type'])->get();

        //get date for humans of today with weekday
        $todayDate = Carbon::now()->locale(
            \session()->get('locale') ??
            config('app.fallback_locale')
        )->isoFormat('dddd, DD.MM.YYYY');

        $notification = $user
            ->notifications()
            ->select(['id', 'data->priority as priority', 'data'])
            ->whereDate('created_at', Carbon::now()->format('Y-m-d'))
            ->withCasts(['created_at' => TimeAgoCast::class])
            ->where('read_at', null)
            ->orderBy('created_at', 'desc');

        if (request('openEditEvent')) {
            $event = Event::find(request('eventId'));
        }

        $historyObjects = [];

        // reload functions
        if (request('showHistory')) {
            if (request('historyType') === 'project') {
                $project = Project::find(request('modelId'));
                $historyComplete = $project->historyChanges()->all();
                foreach ($historyComplete as $history) {
                    $historyObjects[] = [
                        'changes' => json_decode($history->changes),
                        'change_by' => $history->changer,
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
            }

            if (request('historyType') === 'event') {
                $event = Event::find(request('modelId'));
                $historyComplete = $event->historyChanges()->all();
                foreach ($historyComplete as $history) {
                    $historyObjects[] = [
                        'changes' => json_decode($history->changes),
                        'change_by' => $history->changer,
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
            }
        }
        return inertia('Dashboard', [
            'tasks' => TaskDashboardResource::collection($tasks)->resolve(),
            'users_day_services_of_day' => $user->dayServices()->wherePivot('date', $now)->get(),
            'shiftsOfDay' => $shiftsOfDay,
            'todayDate' => $todayDate,
            'eventsOfDay' => $userEvents,
            'globalNotification' => $globalNotificationService->getGlobalNotificationEnrichedByImageUrl(),
            'notificationOfToday' => $notification->get(),
            'notificationCount' => $notification->count(),
            'event' => $event !== null ? new CalendarEventResource($event) : null,
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'rooms' => Room::all(),
            'projects' => Project::all()->map((function ($project) {
                return [
                    'id' => $project->getAttribute('id'),
                    'name' => $project->getAttribute('name'),
                ];
            })),
            'historyObjects' => $historyObjects,
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'first_project_tab_id' => $this->projectTabService->getFirstProjectTabId(),
            'first_project_shift_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),
            'first_project_tasks_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CHECKLIST),
            'first_project_budget_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::BUDGET),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'event_properties' => $eventPropertyService->getAll()
        ]);
    }

    public function viewRequestIndex(): Response
    {
        // Todo: filter room for visible for authenticated user
        // should be like: Event::where($event->room->room_admins->contains(Auth::id()))->map(fn($event) => [
        $events = Event::query()
            ->where('occupancy_option', true)
            ->get();

        return inertia('Events/EventRequestsManagement', [
            'event_requests' => EventShowResource::collection($events)->resolve(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR)
        ]);
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function storeEvent(
        EventStoreRequest $request,
        TableService $tableService,
        ColumnService $columnService,
        MainPositionService $mainPositionService,
        BudgetColumnSettingService $columnSettingService,
        SageApiSettingsService $sageApiSettingsService
    ): CalendarEventResource | RedirectResponse {
        $this->authorize('create', Event::class);
        /** @var Event $firstEvent */
        $firstEvent = Event::create($request->data());
        $firstEvent->eventProperties()->sync($request->get('event_properties'));
        $this->adjoiningRoomsCheck($request, $firstEvent);
        if ($request->get('projectName')) {
            $this->associateProject(
                $request,
                $firstEvent,
                $this->budgetService,
                $tableService,
                $columnService,
                $mainPositionService,
                $columnSettingService,
                $sageApiSettingsService
            );
        }

        /** @var Project $projectFirstEvent */
        $projectFirstEvent = $firstEvent->project;

        if ($request->is_series) {
            $series = SeriesEvents::create([
                'frequency_id' => $request->seriesFrequency,
                'end_date' => $request->seriesEndDate
            ]);
            $firstEvent->update(['is_series' => true, 'series_id' => $series->id]);
            $endSeriesDate = Carbon::parse($request->seriesEndDate)->addDay();
            $startDate = Carbon::parse($request->start)->setTimezone(config('app.timezone'));
            $endDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));
            $whileEndDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));
            if ($request->seriesFrequency === 1) {
                while ($whileEndDate->addDay() < $endSeriesDate) {
                    $startDate = $startDate->addDay();
                    $endDate = $endDate->addDay();
                    $this->createSeriesEvent(
                        $startDate,
                        $endDate,
                        $request,
                        $series,
                        $projectFirstEvent ? $projectFirstEvent->id : null
                    );
                }
            }
            if ($request->seriesFrequency === 2) {
                while ($whileEndDate->addWeek() < $endSeriesDate) {
                    $startDate = $startDate->addWeek();
                    $endDate = $endDate->addWeek();
                    $this->createSeriesEvent(
                        $startDate,
                        $endDate,
                        $request,
                        $series,
                        $projectFirstEvent ? $projectFirstEvent->id : null
                    );
                }
            }
            if ($request->seriesFrequency === 3) {
                while ($whileEndDate->addWeeks(2) < $endSeriesDate) {
                    $startDate = $startDate->addWeeks(2);
                    $endDate = $endDate->addWeeks(2);
                    $this->createSeriesEvent(
                        $startDate,
                        $endDate,
                        $request,
                        $series,
                        $projectFirstEvent ? $projectFirstEvent->id : null
                    );
                }
            }
            if ($request->seriesFrequency === 4) {
                while ($whileEndDate->addMonth() < $endSeriesDate) {
                    $startDate = $startDate->addMonth();
                    $endDate = $endDate->addMonth();
                    $this->createSeriesEvent(
                        $startDate,
                        $endDate,
                        $request,
                        $series,
                        $projectFirstEvent ? $projectFirstEvent->id : null
                    );
                }
            }
        }

        if (!empty($firstEvent->project)) {
            $eventProject = $firstEvent->project;

            if ($eventProject) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($eventProject->id)
                        ->setTranslationKey('Schedule added')
                );
            }
        }

        if ($request->isOption) {
            $this->createRequestNotification($request, $firstEvent);
        }

        broadcast(new OccupancyUpdated())->toOthers();

        if ($request->boolean('showProjectPeriodInCalendar')) {
            return $this->redirector->back();
        }

        broadcast(new EventCreated(
            $firstEvent->load(['event_type']),
            $firstEvent->room_id
        ));

        return new CalendarEventResource($firstEvent);
    }

    private function createSeriesEvent($startDate, $endDate, $request, $series, $projectId): void
    {
        $event = Event::create([
            'name' => $request->title,
            'eventName' => $request->eventName,
            'description' => $request->description,
            'start_time' => $startDate,
            'end_time' => $endDate,
            'occupancy_option' => $request->isOption,
            'audience' => $request->audience,
            'is_loud' => $request->isLoud,
            'event_type_id' => $request->eventTypeId,
            'event_status_id' => $request->eventStatusId,
            'room_id' => $request->roomId,
            'user_id' => Auth::id(),
            'project_id' => $projectId ?: null,
            'is_series' => true,
            'series_id' => $series->id,
            'allDay' => $request->allDay
        ]);
        $event->eventProperties()
            ->sync($request->get('event_properties', []));

        broadcast(new EventCreated($event, $event->room_id));
    }

    public function commitShifts(Request $request): void
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $startDate = Carbon::parse($request->start)->startOfDay();
        $endDate = Carbon::parse($request->end)->endOfDay();

        $this->shiftService->commitShiftsByDate($startDate, $endDate);
    }


    private function adjoiningRoomsCheck(EventStoreRequest $request, $event): void
    {
        $joiningEvents = $this->collisionService->adjoiningCollision($request);
        foreach ($joiningEvents as $joiningEvent) {
            foreach ($joiningEvent as $conflict) {
                $user = User::find($conflict->user_id);
                if ($user->id === Auth::id()) {
                    continue;
                }
                if ($request->audience) {
                    $this->createAdjoiningAudienceNotification($conflict, $user, $event);
                }
                if ($request->isLoud) {
                    $this->createAdjoiningLoudNotification($conflict, $user, $event);
                }
            }
        }

        $collisionsCount = $this->collisionService->getCollision($request, $event)->count();
        if ($collisionsCount > 0) {
            $collisions = $this->collisionService->getConflictEvents($request);
            if (!empty($collisions)) {
                foreach ($collisions as $collision) {
                    $this->createConflictNotification($collision, $event);
                }
            }
        }
    }

    private function createAdjoiningAudienceNotification($conflict, User $user, Event $event): void
    {
        // notification.event.with_adjoining_audience
        $notificationTitle = __('notification.event.with_adjoining_audience', [], $user->language);
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $room = $event->room;
        $project = $event->project;
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room->name,
                'href' => route('rooms.show', $room->id)
            ],
            2 => [
                'type' => 'string',
                'title' =>  $event->event_type->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $project->name,
                'href' => route(
                    'projects.tab',
                    [
                        $project->id,
                        $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                            ProjectTabComponentEnum::CALENDAR
                        )
                    ]
                )
            ],
            4 => [
                'type' => 'string',
                'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                    Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                'href' => null
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setEventId($conflict->id);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_LOUD_ADJOINING_EVENT);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }

    private function createAdjoiningLoudNotification($conflict, User $user, Event $event): void
    {
        $notificationTitle = __('notification.event.adjoining_is_loud', [], $user->language);
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $room = $event->room;
        $project = $event->project;
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room->name,
                'href' => route('rooms.show', $room->id)
            ],
            2 => [
                'type' => 'string',
                'title' =>  $event->event_type->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $project->name,
                'href' => route(
                    'projects.tab',
                    [
                        $project->id,
                        $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                            ProjectTabComponentEnum::CALENDAR
                        )
                    ]
                )
            ],
            4 => [
                'type' => 'string',
                'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                    Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                'href' => null
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setEventId($conflict->id);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_LOUD_ADJOINING_EVENT);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }

    private function createConflictNotification($collision, Event $event): void
    {

        $room = $event->room;
        $project = $event->project;

        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setEventId($collision['event']->id);
        $this->notificationService->setProjectId($collision['event']->project_id);
        $this->notificationService->setRoomId($collision['event']->room_id);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_CONFLICT);


        if (!empty($collision['created_by'])) {
            $notificationTitle = __('notification.event.conflict', [], $collision['created_by']->language);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                0 => [
                    // notification.event.conflict_text
                    //'text' => 'Konflikttermin belegt: ' . Carbon::parse($collision['event']->start_time)
                    //        ->translatedFormat('d.m.Y H:i'),
                    'text' => __(
                        'notification.event.conflict_text',
                        [
                            'date_time' => Carbon::parse($collision['event']->start_time)->translatedFormat('d.m.Y H:i')
                        ],
                        $collision['created_by']->language
                    ),
                    'created_by' => $collision['created_by']
                ],
                1 => [
                    'type' => 'link',
                    'title' => $room->name,
                    'href' => route('rooms.show', $room->id)
                ],
                2 => [
                    'type' => 'string',
                    'title' =>  $event->event_type->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::CALENDAR
                            )
                        ]
                    ) : null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($collision['created_by']);
            $this->notificationService->createNotification();
        }
    }

    private function associateProject(
        $request,
        $event,
        BudgetService $budgetService,
        TableService $tableService,
        ColumnService $columnService,
        MainPositionService $mainPositionService,
        BudgetColumnSettingService $columnSettingService,
        SageApiSettingsService $sageApiSettingsService
    ): void {
        $project = Project::create(['name' => $request->get('projectName')]);
        $budgetService->generateBasicBudgetValues(
            $project,
            $tableService,
            $columnService,
            $mainPositionService,
            $columnSettingService,
            $sageApiSettingsService
        );
        $event->project()->associate($project);
        $event->save();
    }

    private function createRequestNotification($request, Event $event): void
    {
        $room = Room::find($request->roomId);
        $admins = $room->users()->wherePivot('is_admin', true)->get();

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setEventId($event->id);
        $this->notificationService->setRoomId($room->id);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_REQUEST);

        $this->notificationService->setButtons(['accept', 'decline']);
        if (!empty($admins)) {
            foreach ($admins as $admin) {
                // notification.event.new_room_request
                $notificationTitle = __('notification.event.new_room_request', [], $admin->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room->name,
                        'href' => route('rooms.show', $room->id)
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => $event->event_type->name . ', ' . $event->eventName,
                        'href' => null
                    ],
                    3 => [
                        'type' => 'link',
                        'title' => $event->project->name ?? '',
                        'href' => $event->project ?
                            route(
                                'projects.tab',
                                [
                                    $event->project->id,
                                    $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                        ProjectTabComponentEnum::CALENDAR
                                    )
                                ]
                            ) :
                            null
                    ],
                    4 => [
                        'type' => 'string',
                        'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                            Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                        'href' => null
                    ]
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationKey(Str::random(15));
                $this->notificationService->setNotificationTo($admin);
                $this->notificationService->createNotification();
            }
        } else {
            $user = User::find($room->user_id);
            // notification.event.new_room_request
            $notificationTitle = __('notification.event.new_room_request', [], $user->language);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room->name,
                    'href' => route('rooms.show', $room->id)
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $event->project->name ?? '',
                    'href' => $event->project ?
                        route(
                            'projects.tab',
                            [
                                $event->project->id,
                                $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                    ProjectTabComponentEnum::CALENDAR
                                )
                            ]
                        ) :
                        null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationKey(Str::random(15));
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }
    }

    /**
     * @throws AuthorizationException
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function updateEvent(
        EventUpdateRequest $request,
        Event $event,
        ProjectController $projectController
    ): void {
        $this->authorize('update', $event);
        if (!$request->noNotifications) {
            $projectManagers = [];
            $this->notificationService->setNotificationKey(Str::random(15));
            $room = $event->room;
            $project = $event->project;
            if (!empty($project)) {
                $projectManagers = $project->managerUsers()->get();
            }
            if (!empty($request->adminComment)) {
                $projectManagers = [];
                $this->notificationService->setNotificationKey(Str::random(15));
                $project = $event->project;
                if (!empty($project)) {
                    $projectManagers = $project->managerUsers()->get();
                }
                $event->comments()->create([
                    'user_id' => Auth::id(),
                    'comment' => $request->adminComment,
                    'is_admin_comment' => true
                ]);

                $this->notificationService->setIcon('blue');
                $this->notificationService->setPriority(1);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_ANSWER);

                $this->notificationService->setRoomId($room->id);
                $this->notificationService->setEventId($event->id);
                $this->notificationService->setButtons(['answerDialog']);
                foreach ($projectManagers as $projectManager) {
                    if ($projectManager->id === $event->creator) {
                        continue;
                    }
                    $notificationTitle = __('notification.event.admin_message', [], $projectManager->language);
                    $broadcastMessage = [
                        'id' => rand(1, 1000000),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];

                    $event->save();
                    $notificationDescription = [
                        1 => [
                            'type' => 'link',
                            'title' => $room->name,
                            'href' => route('rooms.show', $room->id)
                        ],
                        2 => [
                            'type' => 'string',
                            'title' => $event->event_type->name . ', ' . $event->eventName,
                            'href' => null
                        ],
                        3 => [
                            'type' => 'link',
                            'title' => $project ? $project->name : '',
                            'href' => $project ? route(
                                'projects.tab',
                                [
                                    $project->id,
                                    $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                        ProjectTabComponentEnum::CALENDAR
                                    )
                                ]
                            ) : null
                        ],
                        4 => [
                            'type' => 'string',
                            'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                                Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                            'href' => null
                        ],
                        5 => [
                            'type' => 'comment',
                            'title' => $request->adminComment,
                            'href' => null
                        ]
                    ];
                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setNotificationKey(Str::random(15));
                    $this->notificationService->setNotificationTo($projectManager);
                    $this->notificationService->createNotification();
                }
                $notificationTitle = __('notification.event.admin_message', [], $event->creator->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];

                $this->eventService->save($event);
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room->name,
                        'href' => route('rooms.show', $room->id)
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => $event->event_type->name . ', ' . $event->eventName,
                        'href' => null
                    ],
                    3 => [
                        'type' => 'link',
                        'title' => $project ? $project->name : '',
                        'href' => $project ? route(
                            'projects.tab',
                            [
                                $project->id,
                                $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                    ProjectTabComponentEnum::CALENDAR
                                )
                            ]
                        ) : null
                    ],
                    4 => [
                        'type' => 'string',
                        'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                            Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                        'href' => null
                    ],
                    5 => [
                        'type' => 'comment',
                        'title' => $request->adminComment,
                        'href' => null
                    ]
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationKey(Str::random(15));
                $this->notificationService->setNotificationTo($event->creator);
                $this->notificationService->createNotification();
            }
        }

        if ($request->roomChange) {
            $room = Room::find($event->room_id);
            $project = Project::find($event->project_id);


            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_CHANGED);
            $this->notificationService->setRoomId($event->room_id);
            $this->notificationService->setEventId($event->id);

            foreach ($projectManagers as $projectManager) {
                if ($projectManager->id === $event->creator) {
                    continue;
                }
                $notificationTitle = __('notification.event.room_change_confirmed', [], $projectManager->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room->name,
                        'href' => route('rooms.show', $room->id)
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => $event->event_type->name . ', ' . $event->eventName,
                        'href' => null
                    ],
                    3 => [
                        'type' => 'link',
                        'title' => $project ? $project->name : '',
                        'href' => $project ? route(
                            'projects.tab',
                            [
                                $project->id,
                                $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                    ProjectTabComponentEnum::CALENDAR
                                )
                            ]
                        ) : null
                    ],
                    4 => [
                        'type' => 'string',
                        'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                            Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                        'href' => null
                    ],
                    5 => [
                        'type' => 'comment',
                        'title' => $request->adminComment,
                        'href' => null
                    ]
                ];

                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationTo($projectManager);
                $this->notificationService->createNotification();
            }
            $notificationTitle = __(
                'notification.event.room_change_confirmed',
                [],
                $event->creator->language
            );
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room->name,
                    'href' => route('rooms.show', $room->id)
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::CALENDAR
                            )
                        ]
                    ) : null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ],
                5 => [
                    'type' => 'comment',
                    'title' => $request->adminComment,
                    'href' => null
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($event->creator);
            $this->notificationService->createNotification();
        }

        $oldEventDescription = $event->description;
        $oldEventRoom = $event->room_id;
        $oldEventProject = $event->project_id;
        $oldEventName = $event->eventName;
        $oldEventType = $event->event_type_id;
        $oldEventStartDate = $event->start_time;
        $oldEventEndDate = $event->end_time;
        $oldEventPropertyIds = $event->getAttribute('eventProperties')->map(
            fn (EventProperty $eventProperty) => $eventProperty->getAttribute('id')
        )->all();

        $event->fill($request->data());
        $event->eventProperties()->sync(($newEventPropertyIds = $request->get('event_properties', [])));

        $this->eventService->save($event);

        if ($request->get('projectName')) {
            $project = Project::create(['name' => $request->get('projectName')]);
            $project->users()->save(Auth::user(), ['access_budget' => true]);
            $projectController->generateBasicBudgetValues($project);
            $event->project()->associate($project);
            $this->eventService->save($event);
        }

        if (!empty($event->project_id)) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($event->project->id)
                    ->setTranslationKey('Schedule modified')
            );
        }

        $newEventDescription = $event->description;
        $newEventRoom = $event->room_id;
        $newEventProject = $event->project_id;
        $newEventName = $event->eventName;
        $newEventType = $event->event_type_id;
        $newEventStartDate = $event->start_time;
        $newEventEndDate = $event->end_time;

        $this->checkShortDescriptionChanges($event->id, $oldEventDescription, $newEventDescription);
        $this->checkRoomChanges($event->id, $oldEventRoom, $newEventRoom);
        $this->checkProjectChanges($event->id, $oldEventProject, $newEventProject);
        $this->checkEventNameChanges($event->id, $oldEventName, $newEventName);
        $this->checkEventTypeChanges($event->id, $oldEventType, $newEventType);
        $this->checkDateChanges($event->id, $oldEventStartDate, $newEventStartDate, $oldEventEndDate, $newEventEndDate);
        $this->checkEventPropertyChanges($event->id, $oldEventPropertyIds, $newEventPropertyIds);

        $this->createEventScheduleNotification($event);

        // get time diff
        $oldEventStartDateDays = Carbon::create($oldEventStartDate);
        $oldEventEndDateDays = Carbon::create($oldEventEndDate);

        $newEventStartDateDays = Carbon::parse($newEventStartDate);
        $newEventEndDateDays = Carbon::parse($newEventEndDate);

        $diffStartDays = $oldEventStartDateDays->diffInDays($newEventStartDateDays, false);
        $diffEndDays = $oldEventEndDateDays->diffInDays($newEventEndDateDays, false);

        $diffStartMinutes = $oldEventStartDateDays->diffInRealMinutes($newEventStartDateDays, false);
        $diffEndMinutes = $oldEventEndDateDays->diffInRealMinutes($newEventEndDateDays, false);

        if ($request->allSeriesEvents) {
            if ($event->is_series) {
                $seriesEvents = Event::where('series_id', $event->series_id)->get();
                foreach ($seriesEvents as $seriesEvent) {
                    // Guard
                    if ($seriesEvent->id === $event->id) {
                        continue;
                    }

                    $startDay = Carbon::create($seriesEvent->start_time)
                        ->addDays($diffStartDays)
                        ->format('Y-m-d');
                    $endDay = Carbon::create($seriesEvent->end_time)
                        ->addDays($diffEndDays)
                        ->format('Y-m-d');

                    $startTime = Carbon::create($seriesEvent->start_time)
                        ->addMinutes($diffStartMinutes)
                        ->format('H:i:s');
                    $endTime = Carbon::create($seriesEvent->end_time)
                        ->addMinutes($diffEndMinutes)
                        ->format('H:i:s');

                    $seriesEvent->update([
                        'name' => $event->name,
                        'eventName' => $event->eventName,
                        'description' => $event->description,
                        'occupancy_option' => $event->occupancy_option,
                        'audience' => $event->audience,
                        'is_loud' => $event->is_loud,
                        'event_type_id' => $event->event_type_id,
                        'room_id' => $event->room_id,
                        'project_id' => $event->project_id,
                        'start_time' => $startDay . ' ' . $startTime,
                        'end_time' => $endDay . ' ' . $endTime,
                    ]);
                }
                // date shifts with date
            }
        }

        $shifts = Shift::where('event_id', $event->id)->get();
        foreach ($shifts as $shift) {
            $startDay = Carbon::create($shift->start_date)
                ->addDays($diffStartDays)
                ->format('Y-m-d');
            $endDay = Carbon::create($shift->end_date)
                ->addDays($diffEndDays)
                ->format('Y-m-d');

            $shift->update([
                'start_date' => $startDay,
                'end_date' => $endDay,
            ]);
        }

        // update event time in inventory
        if ($isInInventoryEvent = $this->craftInventoryItemEventService->checkIfEventIsInInventoryPlaning($event)) {
            $this->craftInventoryItemEventService->updateEventTimeInInventory($isInInventoryEvent, $event);
        }

        broadcast(new EventCreated(
            $event->fresh(),
            $event->fresh()->room_id
        ));

        //redirect is required for bulk component event component
        /*if ($request->boolean('usedInBulkComponent')) {
            return $this->redirector->back();
        }*/
        //return new CalendarEventResource($event);
    }

    private function createEventScheduleNotification(Event $event): void
    {
        if (!empty($event->project)) {
            foreach ($event->project->users->all() as $eventUser) {
                $this->schedulingService->create($eventUser->id, 'EVENT_CHANGES', 'EVENT', $event->id);
            }
        } else {
            $this->schedulingService->create($event->creator->id, 'EVENT_CHANGES', 'EVENT', $event->id);
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function answerOnEvent(Event $event, Request $request): void
    {
        $this->authorize('update', $event);

        $event->comments()->create([
            'user_id' => Auth::id(),
            'comment' => $request->comment,
            'is_admin_comment' => false
        ]);

        $this->notificationService->setNotificationKey(Str::random(15));
        $room = Room::find($event->room_id);
        $admins = collect();
        if (!empty($room)) {
            $admins = $room->users()->wherePivot('is_admin', true)->get();
        }

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_REQUEST);
        $this->notificationService->setRoomId($event->room_id);
        $this->notificationService->setEventId($event->id);
        $this->notificationService->setButtons(['accept', 'decline']);
        if ($admins->count() > 0) {
            foreach ($admins as $admin) {
                $notificationTitle = __('notification.event.new_message', [], $admin->language);
                $broadcastMessage = [
                    'id' => random_int(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room->name,
                        'href' => route('rooms.show', $room->id)
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                        'href' => null
                    ],
                    3 => [
                        'type' => 'link',
                        'title' => $event->project()->first()->name ?? '',
                        'href' => $event->project()->first() ?
                            route(
                                'projects.tab',
                                [
                                    $event->project->id,
                                    $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                        ProjectTabComponentEnum::CALENDAR
                                    )
                                ]
                            ) :
                            null
                    ],
                    4 => [
                        'type' => 'string',
                        'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                            Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                        'href' => null
                    ],
                    5 => [
                        'type' => 'comment',
                        'title' => $request->comment,
                        'href' => null
                    ]
                ];

                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationTo($admin);
                $this->notificationService->createNotification();
            }
        } else {
            $user = User::find($room->user_id);
            $notificationTitle = __('notification.event.new_message', [], $user->language);
            $broadcastMessage = [
                'id' => random_int(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room->name,
                    'href' => route('rooms.show', $room->id)
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $event->project()->first()->name ?? '',
                    'href' => $event->project()->first() ?
                        route(
                            'projects.tab',
                            [
                                $event->project->id,
                                $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                    ProjectTabComponentEnum::CALENDAR
                                )
                            ]
                        ) :
                        null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ],
                5 => [
                    'type' => 'comment',
                    'title' => $request->comment,
                    'href' => null
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }
    }

    public function deleteOldNotifications(Request $request): void
    {
        $notifications = DatabaseNotification::query()
            ->whereJsonContains("data->notificationKey", $request->notificationKey)
            ->get();

        foreach ($notifications as $notification) {
            $notification->delete();
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function acceptEvent(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->occupancy_option = false;

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Event::class)
                ->setModelId($event->id)
                ->setTranslationKey('Room confirmed')
        );

        $event->save();
        $room = Room::find($event->room_id);
        $project = Project::find($event->project_id);
        $projectManagers = collect();
        if (!empty($project)) {
            $projectManagers = $project->managerUsers()->get();
        }
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST);

        $this->notificationService->setRoomId($event->room_id);
        $this->notificationService->setEventId($event->id);
        $this->notificationService->setProjectId($event->project_id);
        foreach ($projectManagers as $projectManager) {
            if ($projectManager->id === $event->creator) {
                continue;
            }
            $notificationTitle = __('notification.event.room_request_accept', [], $projectManager->language);
            $broadcastMessage = [
                'id' => random_int(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room->name,
                    'href' => route('rooms.show', $room->id)
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::CALENDAR
                            )
                        ]
                    ) : null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ],
                5 => [
                    'type' => 'comment',
                    'title' => $request->adminComment,
                    'href' => null
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($projectManager);
            $this->notificationService->createNotification();
        }
        $notificationTitle = __('notification.event.room_request_accept', [], $event->creator()->first()->language);
        $broadcastMessage = [
            'id' => random_int(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room->name,
                'href' => route('rooms.show', $room->id)
            ],
            2 => [
                'type' => 'string',
                'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $project ? $project->name : '',
                'href' => $project ? route(
                    'projects.tab',
                    [
                        $project->id,
                        $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                            ProjectTabComponentEnum::CALENDAR
                        )
                    ]
                ) : null
            ],
            4 => [
                'type' => 'string',
                'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                    Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                'href' => null
            ],
            5 => [
                'type' => 'comment',
                'title' => $request->adminComment,
                'href' => null
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($event->creator);
        $this->notificationService->createNotification();

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function declineEvent(Request $request, Event $event): void
    {
        $this->authorize('update', $event);

        $projectManagers = [];
        $roomId = $event->room_id;
        $room = $event->room()->first();
        $project = $event->project()->first();
        if (!empty($project)) {
            $projectManagers = $project->managerUsers()->get();
        }
        $event->update(['accepted' => false, 'declined_room_id' => $roomId, 'room_id' => null]);

        if (!empty($request->comment)) {
            $event->comments()->create([
                'user_id' => Auth::id(),
                'comment' => $request->comment,
                'is_admin_comment' => true
            ]);
            $event->save();


            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_ANSWER);
            $this->notificationService->setRoomId($event->room_id);
            $this->notificationService->setEventId($event->id);
            $this->notificationService->setProjectId($event->project_id);
            $this->notificationService->setButtons(['answer']);
            foreach ($projectManagers as $projectManager) {
                if ($projectManager->id === $event->creator) {
                    continue;
                }
                $notificationTitle = __('notification.event.admin_message', [], $projectManager->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room->name,
                        'href' => route('rooms.show', $room->id)
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                        'href' => null
                    ],
                    3 => [
                        'type' => 'link',
                        'title' => $project ? $project->name : '',
                        'href' => $project ? route(
                            'projects.tab',
                            [
                                $project->id,
                                $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                    ProjectTabComponentEnum::CALENDAR
                                )
                            ]
                        ) : null
                    ],
                    4 => [
                        'type' => 'string',
                        'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                            Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                        'href' => null
                    ],
                    5 => [
                        'type' => 'comment',
                        'title' => $request->comment,
                        'href' => null
                    ]
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationKey(Str::random(15));
                $this->notificationService->setNotificationTo($projectManager);
                $this->notificationService->createNotification();
            }
            $notificationTitle = __('notification.event.admin_message', [], $event->creator()->first()->language);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room ? $room->name : 'Without Room',
                    'href' => $room ? route('rooms.show', $room->id) : '#'
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::CALENDAR
                            )
                        ]
                    ) : null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ],
                5 => [
                    'type' => 'comment',
                    'title' => $request->comment,
                    'href' => null
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationKey(Str::random(15));
            $this->notificationService->setNotificationTo($event->creator);
            $this->notificationService->createNotification();
        }

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Event::class)
                ->setModelId($event->id)
                ->setTranslationKey('Room declined')
        );

        // Make sure $room is not null before using it
        if (!$room) {
            $room = Room::find($roomId);
        }
        $project = Project::find($event->project_id);

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST);

        $this->notificationService->setRoomId($event->room_id);
        $this->notificationService->setEventId($event->id);
        $this->notificationService->setProjectId($event->project_id);
        $this->notificationService->setButtons(['change_request', 'event_delete']);
        foreach ($projectManagers as $projectManager) {
            if ($projectManager->id === $event->creator) {
                continue;
            }
            $notificationTitle = __('notification.event.room_request_declined', [], $projectManager->language);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room ? $room->name : 'Without Room',
                    'href' => $room ? route('rooms.show', $room->id) : '#'
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::CALENDAR
                            )
                        ]
                    ) : null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ],
                5 => [
                    'type' => 'comment',
                    'title' => $request->comment,
                    'href' => null
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationKey(Str::random(15));
            $this->notificationService->setNotificationTo($projectManager);
            $this->notificationService->createNotification();
        }
        $notificationTitle = __('notification.event.room_request_declined', [], $event->creator()->first()->language);
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room ? $room->name : 'Without Room',
                'href' => $room ? route('rooms.show', $room->id) : '#'
            ],
            2 => [
                'type' => 'string',
                'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $project ? $project->name : '',
                'href' => $project ? route(
                    'projects.tab',
                    [
                        $project->id,
                        $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                            ProjectTabComponentEnum::CALENDAR
                        )
                    ]
                ) : null
            ],
            4 => [
                'type' => 'string',
                'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                    Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                'href' => null
            ],
            5 => [
                'type' => 'comment',
                'title' => $request->comment,
                'href' => null
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationKey(Str::random(15));
        $this->notificationService->setNotificationTo($event->creator);
        $this->notificationService->createNotification();

        broadcast(new EventCreated(
            $event,
            $roomId
        ));
    }

    public function getCollisionCount(Request $request): int
    {
        $start = Carbon::parse($request->query('start'))->setTimezone(config('app.timezone'));
        $end = Carbon::parse($request->query('end'))->setTimezone(config('app.timezone'));

        return Event::query()
            ->startAndEndTimeOverlap($start, $end)
            ->where('room_id', $request->query('roomId'))
            ->where('id', '!=', $request->query('eventId'))
            ->count();
    }

    public function getTrashed(): Response|ResponseFactory
    {
        return inertia('Trash/Events', [
            'trashed_events' => Event::onlyTrashed()->get()->map(fn ($event) => [
                'id' => $event->id,
                'name' => $event->eventName,
                'project' => $event->project,
                'event_type' => $event->event_type,
                'start' => $event->start_time->format('d.m.Y, H:i'),
                'end' => $event->end_time->format('d.m.Y, H:i'),
                'room_name' => $event->room?->label,
            ]),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR)
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroyShifts(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->shiftService->forceDeleteShifts($event->shifts);
        $this->timelineService->forceDeleteTimelines($event->timelines);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function destroy(
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
        $this->authorize('delete', $event);

        $this->eventService->delete(
            $event,
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService,
            $changeService,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService,
            $projectTabService
        );

        if ($isInInventoryEvent = $this->craftInventoryItemEventService->checkIfEventIsInInventoryPlaning($event)) {
            $this->craftInventoryItemEventService->deleteEventFromInventory($isInInventoryEvent);
        }

        //return true;
    }

    public function destroyWithoutReturn(
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
        $this->authorize('delete', $event);

        $this->eventService->delete(
            $event,
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService,
            $changeService,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService,
            $projectTabService
        );

        if ($isInInventoryEvent = $this->craftInventoryItemEventService->checkIfEventIsInInventoryPlaning($event)) {
            $this->craftInventoryItemEventService->deleteEventFromInventory($isInInventoryEvent);
        }
    }

    /**
     * @throws AuthorizationException
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function destroyByNotification(
        Event $event,
        Request $request,
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
        $this->authorize('delete', $event);

        if (!empty($event->project)) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($event->project->id)
                    ->setTranslationKey('Schedule deleted')
            );
        }

        if (!empty($request->notificationKey)) {
            $notifications = DatabaseNotification::query()
                ->whereJsonContains("data->notificationKey", $request->notificationKey)
                ->get();

            foreach ($notifications as $notification) {
                $notification->delete();
            }
        }

        $this->eventService->delete(
            $event,
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService,
            $changeService,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService,
            $projectTabService
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($id);

        $this->authorize('delete', $event);
        $event->subEvents()->forceDelete();
        $event->forceDelete();

        return Redirect::route('events.trashed');
    }

    public function restore(int $id): RedirectResponse
    {
        /** @var Event $event */
        $event = Event::onlyTrashed()->findOrFail($id);
        $event->subEvents()->restore();
        $event->restore();

        if (!$event->project()->exists()) {
            $event->project_id = null;
            $event->save();
        }

        return Redirect::route('events.trashed');
    }

    private function checkDateChanges(
        $eventId,
        $oldEventStartDate,
        $newEventStartDate,
        $oldEventEndDate,
        $newEventEndDate
    ): void {
        if (
            strtotime($oldEventStartDate) !== strtotime($newEventStartDate) ||
            strtotime($oldEventEndDate) !== strtotime($newEventEndDate)
        ) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Date/time changed')
            );
        }
    }

    private function checkEventTypeChanges($eventId, $oldType, $newType): void
    {
        if ($oldType !== $newType) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Appointment type changed')
            );
        }
    }

    private function checkEventNameChanges($eventId, $oldName, $newName): void
    {
        if ($oldName === null && $newName !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Appointment name added')
            );
        }

        if ($oldName !== $newName && $newName !== null && $oldName !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Appointment name changed')
            );
        }

        if ($oldName !== null && $newName === null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Appointment name deleted')
            );
        }
    }

    private function checkProjectChanges($eventId, $oldProject, $newProject): void
    {
        if ($newProject !== null && $oldProject === null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Added project assignment')
            );
        }

        if ($oldProject !== null && $newProject === null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Deleted project assignment')
            );
        }

        if ($newProject !== null && $oldProject !== null && $newProject !== $oldProject) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Changed project assignment')
            );
        }
    }

    private function checkRoomChanges($eventId, $oldRoom, $newRoom): void
    {
        if ($oldRoom !== $newRoom) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Room changed')
            );

            $this->notificationService->deleteUpsertRoomRequestNotificationByEventId($eventId);
        }
    }

    private function checkShortDescriptionChanges(int $eventId, $oldDescription, $newDescription): void
    {
        if ($newDescription === null && $oldDescription !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Appointment notice deleted')
            );
        }
        if ($oldDescription === null && $newDescription !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Appointment notice added')
            );
        }
        if ($oldDescription !== $newDescription && $oldDescription !== null && $newDescription !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Appointment notice changed')
            );
        }
    }

    private function checkEventPropertyChanges(
        int $eventId,
        array $oldEventPropertyIds,
        array $newEventPropertyIds
    ): void {
        if (
            array_diff($oldEventPropertyIds, $newEventPropertyIds) ||
            array_diff($newEventPropertyIds, $oldEventPropertyIds)
        ) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Changed appointment property')
            );
        }
    }

    public function deleteMultiEdit(
        Request $request,
        EventService $eventService,
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
    ): bool {
        foreach ($request->collect('events') as $eventId) {
            $eventService->delete(
                $eventService->findEventById($eventId),
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService,
                $changeService,
                $eventCommentService,
                $timelineService,
                $shiftService,
                $subEventService,
                $notificationService,
                $projectTabService
            );
        }

        return true;
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.TooHigh
    public function updateMultiEdit(Request $request): void
    {
        $desiredRoomIds = [];
        $desiredDaysOfEvents = [];

        $eventIds = $request->collect('events');
        foreach ($eventIds as $eventId) {
            $event = $this->eventService->findEventById($eventId);
            $desiredRoomIds[] = $event->getAttribute('room_id');

            foreach (
                CarbonPeriod::create(
                    $event->getAttribute('start_time'),
                    $event->getAttribute('end_time')
                ) as $desiredDayOfEvent
            ) {
                $desiredDaysOfEvents[] = $desiredDayOfEvent->format('d.m.Y');
            }

            if ($request->get('newRoomId') !== null) {
                $event->setAttribute('room_id', $request->integer('newRoomId'));
                $desiredRoomIds[] = $event->getAttribute('room_id');
            }

            if ($request->string('date')->toString() === '') {
                if ($request->integer('value') !== 0) {
                    $endDate = Carbon::parse($event->getAttribute('end_time'));
                    $startDate = Carbon::parse($event->getAttribute('start_time'));
                    $shifts = $event->getAttribute('shifts');
                    $calculationType = $request->integer('calculationType');
                    $value = $request->integer('value');
                    $type = $request->integer('type');

                    // plus
                    if ($calculationType === 1) {
                        // stunden
                        if ($type === 1) {
                            $event->setAttribute('start_time', $startDate->addHours($value));
                            $event->setAttribute('end_time', $endDate->addHours($value));
                        }

                        // Tage
                        if ($type === 2) {
                            $event->setAttribute('start_time', $startDate->addDays($value));
                            $event->setAttribute('end_time', $endDate->addDays($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->addDays($value));
                                $shift->setAttribute('end_date', $shiftEnd->addDays($value));
                                $shift->save();
                            }
                        }
                        // Wochen
                        if ($type === 3) {
                            $event->setAttribute('start_time', $startDate->addWeeks($value));
                            $event->setAttribute('end_time', $endDate->addWeeks($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->addWeeks($value));
                                $shift->setAttribute('end_date', $shiftEnd->addWeeks($value));
                                $shift->save();
                            }
                        }
                        // Monate
                        if ($type === 4) {
                            $event->setAttribute('start_time', $startDate->addMonths($value));
                            $event->setAttribute('end_time', $endDate->addMonths($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->addMonths($value));
                                $shift->setAttribute('end_date', $shiftEnd->addMonths($value));
                                $shift->save();
                            }
                        }
                        // Jahre
                        if ($type === 5) {
                            $event->setAttribute('start_time', $startDate->addYears($value));
                            $event->setAttribute('end_time', $endDate->addYears($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->addYears($value));
                                $shift->setAttribute('end_date', $shiftEnd->addYears($value));
                                $shift->save();
                            }
                        }
                    }

                    // minus
                    if ($calculationType === 2) {
                        // stunden
                        if ($type === 1) {
                            $event->setAttribute('start_time', $startDate->subHours($value));
                            $event->setAttribute('end_time', $endDate->subHours($value));
                        }
                        // Tage
                        if ($type === 2) {
                            $event->setAttribute('start_time', $startDate->subDays($value));
                            $event->setAttribute('end_time', $endDate->subDays($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->subDays($value));
                                $shift->setAttribute('end_date', $shiftEnd->subDays($value));
                                $shift->save();
                            }
                        }
                        // Wochen
                        if ($type === 3) {
                            $event->setAttribute('start_time', $startDate->subWeeks($value));
                            $event->setAttribute('end_time', $endDate->subWeeks($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->subWeeks($value));
                                $shift->setAttribute('end_date', $shiftEnd->subWeeks($value));
                                $shift->save();
                            }
                        }
                        // Monate
                        if ($type === 4) {
                            $event->setAttribute('start_time', $startDate->subMonths($value));
                            $event->setAttribute('end_time', $endDate->subMonths($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->subMonths($value));
                                $shift->setAttribute('end_date', $shiftEnd->subMonths($value));
                                $shift->save();
                            }
                        }
                        // Jahre
                        if ($type === 5) {
                            $event->setAttribute('start_time', $startDate->subYears($value));
                            $event->setAttribute('end_time', $endDate->subYears($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->subYears($value));
                                $shift->setAttribute('end_date', $shiftEnd->subYears($value));
                                $shift->save();
                            }
                        }
                    }
                }
                $desiredDaysOfEvents[] = $event->getAttribute('start_time')->format('d.m.Y');
                $desiredDaysOfEvents[] = $event->getAttribute('end_time')->format('d.m.Y');
            } else {
                $endTime = Carbon::parse($event->getAttribute('end_time'))->format('H:i:s');
                $startTime = Carbon::parse($event->getAttribute('start_time'))->format('H:i:s');

                $newDate = Carbon::parse($request->string('date'));
                $desiredDaysOfEvents[] = $newDate->format('d.m.Y');
                $date = $newDate->format('Y-m-d');
                $event->setAttribute('start_time', $date . ' ' . $startTime);
                $event->setAttribute('end_time', $date . ' ' . $endTime);
            }
            $event->save();
            broadcast(new EventCreated(
                $event->fresh(),
                $event->fresh()->room_id
            ));
        }


        /*return new JsonResponse([
            'desiredRoomIds' => array_values(array_unique($desiredRoomIds)),
            'desiredDays' => array_values(array_unique($desiredDaysOfEvents))
        ]);*/
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.TooHigh
    public function updateMultiDuplicate(Request $request): void
    {
        $desiredRoomIds = [];
        $desiredDaysOfEvents = [];
        $eventIds = $request->collect('events');
        $duplicatedEvents = [];

        foreach ($eventIds as $eventId) {
            $originalEvent = $this->eventService->findEventById($eventId);

            $duplicatedEvent = $originalEvent->replicate();
            $duplicatedEvent->save();

            $shifts = $originalEvent->shifts;
            foreach ($shifts as $shift) {
                $duplicatedShift = $shift->replicate();
                $duplicatedShift->event_id = $duplicatedEvent->id;
                $duplicatedShift->save();
            }
            $duplicatedEvents[] = $duplicatedEvent;
        }

        foreach ($duplicatedEvents as $event) {
            $desiredRoomIds[] = $event->getAttribute('room_id');

            foreach (
                CarbonPeriod::create(
                    $event->getAttribute('start_time'),
                    $event->getAttribute('end_time')
                ) as $desiredDayOfEvent
            ) {
                $desiredDaysOfEvents[] = $desiredDayOfEvent->format('d.m.Y');
            }

            if ($request->get('newRoomId') !== null) {
                $event->setAttribute('room_id', $request->integer('newRoomId'));
                $desiredRoomIds[] = $event->getAttribute('room_id');
            }
            if ($request->string('date')->toString() === '') {
                if ($request->integer('value') !== 0) {
                    $endDate = Carbon::parse($event->getAttribute('end_time'));
                    $startDate = Carbon::parse($event->getAttribute('start_time'));
                    $shifts = $event->getAttribute('shifts');
                    $calculationType = $request->integer('calculationType');
                    $value = $request->integer('value');
                    $type = $request->integer('type');

                    // plus
                    if ($calculationType === 1) {
                        // stunden
                        if ($type === 1) {
                            $event->setAttribute('start_time', $startDate->addHours($value));
                            $event->setAttribute('end_time', $endDate->addHours($value));
                        }

                        // Tage
                        if ($type === 2) {
                            $event->setAttribute('start_time', $startDate->addDays($value));
                            $event->setAttribute('end_time', $endDate->addDays($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->addDays($value));
                                $shift->setAttribute('end_date', $shiftEnd->addDays($value));
                                $shift->save();
                            }
                        }
                        // Wochen
                        if ($type === 3) {
                            $event->setAttribute('start_time', $startDate->addWeeks($value));
                            $event->setAttribute('end_time', $endDate->addWeeks($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->addWeeks($value));
                                $shift->setAttribute('end_date', $shiftEnd->addWeeks($value));
                                $shift->save();
                            }
                        }
                        // Monate
                        if ($type === 4) {
                            $event->setAttribute('start_time', $startDate->addMonths($value));
                            $event->setAttribute('end_time', $endDate->addMonths($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->addMonths($value));
                                $shift->setAttribute('end_date', $shiftEnd->addMonths($value));
                                $shift->save();
                            }
                        }
                        // Jahre
                        if ($type === 5) {
                            $event->setAttribute('start_time', $startDate->addYears($value));
                            $event->setAttribute('end_time', $endDate->addYears($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->addYears($value));
                                $shift->setAttribute('end_date', $shiftEnd->addYears($value));
                                $shift->save();
                            }
                        }
                    }

                    // minus
                    if ($calculationType === 2) {
                        // stunden
                        if ($type === 1) {
                            $event->setAttribute('start_time', $startDate->subHours($value));
                            $event->setAttribute('end_time', $endDate->subHours($value));
                        }
                        // Tage
                        if ($type === 2) {
                            $event->setAttribute('start_time', $startDate->subDays($value));
                            $event->setAttribute('end_time', $endDate->subDays($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->subDays($value));
                                $shift->setAttribute('end_date', $shiftEnd->subDays($value));
                                $shift->save();
                            }
                        }
                        // Wochen
                        if ($type === 3) {
                            $event->setAttribute('start_time', $startDate->subWeeks($value));
                            $event->setAttribute('end_time', $endDate->subWeeks($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->subWeeks($value));
                                $shift->setAttribute('end_date', $shiftEnd->subWeeks($value));
                                $shift->save();
                            }
                        }
                        // Monate
                        if ($type === 4) {
                            $event->setAttribute('start_time', $startDate->subMonths($value));
                            $event->setAttribute('end_time', $endDate->subMonths($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->subMonths($value));
                                $shift->setAttribute('end_date', $shiftEnd->subMonths($value));
                                $shift->save();
                            }
                        }
                        // Jahre
                        if ($type === 5) {
                            $event->setAttribute('start_time', $startDate->subYears($value));
                            $event->setAttribute('end_time', $endDate->subYears($value));
                            foreach ($shifts as $shift) {
                                $shiftStart = Carbon::parse($shift->getAttribute('start_date'));
                                $shiftEnd = Carbon::parse($shift->getAttribute('end_date'));
                                $shift->setAttribute('start_date', $shiftStart->subYears($value));
                                $shift->setAttribute('end_date', $shiftEnd->subYears($value));
                                $shift->save();
                            }
                        }
                    }
                }

                foreach (
                    CarbonPeriod::create(
                        $event->getAttribute('start_time'),
                        $event->getAttribute('end_time')
                    ) as $desiredDayOfEvent
                ) {
                    $desiredDaysOfEvents[] = $desiredDayOfEvent->format('d.m.Y');
                }
            } else {
                $endTime = Carbon::parse($event->getAttribute('end_time'))->format('H:i:s');
                $startTime = Carbon::parse($event->getAttribute('start_time'))->format('H:i:s');

                $newDate = Carbon::parse($request->string('date'));
                $desiredDaysOfEvents[] = $newDate->format('d.m.Y');
                $date = $newDate->format('Y-m-d');
                $event->setAttribute('start_time', $date . ' ' . $startTime);
                $event->setAttribute('end_time', $date . ' ' . $endTime);
            }
            $event->save();
            broadcast(new EventCreated(
                $event->fresh(),
                $event->fresh()->room_id
            ));
        }
    }


    public function bulkProjectEventStore(
        EventBulkCreateRequest $request,
        Project $project
    ): RedirectResponse {
        $events = $request->input('events', []);

        foreach ($events as $event) {
            $this->eventService->createBulkEvent(
                $event,
                $project,
                $this->authManager->id()
            );
        }

        return Redirect::back();
    }

    public function updateSingleBulkEvent(
        Request $request,
        Event $event
    ): RedirectResponse {
        $data =  $request->collect('data');
        $this->eventService->updateBulkEvent(
            $data,
            $event
        );

        $freshEvent = $event->fresh();
        broadcast(new EventCreated(
            $event->load(['project', 'event_type']),
            $event->room_id
        ));

        return Redirect::back();
    }

    public function createSingleBulkEvent(
        Request $request,
        Project $project
    ): RedirectResponse {
        $data =  $request->input('event', []);



        $event = $this->eventService->createBulkEvent(
            $data,
            $project,
            $this->authManager->id()
        );


        broadcast(new EventCreated(
            $event,
            $event->room_id
        ));

        return Redirect::back();
    }

    public function updateDescription(Request $request, Event $event): RedirectResponse
    {
        $event->update($request->only(['description']));

        broadcast(new EventUpdated(
            $event->room_id,
            $event->start_time,
            $event->is_series ?
                $event->series->end_date :
                $event->end_time
        ))->toOthers();

        return $this->redirector->back();
    }


    public function bulkMultiEditEvent(Request $request): void
    {
        $this->eventService->bulkMultiEditEvent(
            $request->collect('eventIds'),
            $request->only([
                'selectedRoom',
                'selectedEventType',
                'selectedEventStatus',
                'eventName',
                'selectedDay',
                'selectedStartTime',
                'selectedEndTime'
            ])
        );
    }

    public function bulkDeleteEvent(Request $request): void
    {
        $this->eventService->bulkDeleteEvent($request->collect('eventIds'));
    }
}
