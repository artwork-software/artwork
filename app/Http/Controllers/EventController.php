<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetShiftPlanWorkersRequest;
use App\Settings\EventSettings;
use App\Settings\ShiftSettings;
use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Casts\TimeAgoCast;
use Artwork\Core\Services\HelperService;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\MainPositionService;
use Artwork\Modules\Budget\Services\TableService;
use Artwork\Modules\Budget\Services\BudgetColumnSettingService;
use Artwork\Modules\Calendar\DTO\EventWithoutRoomDTO;
use Artwork\Modules\Calendar\DTO\RoomDTO;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\EventCalendarService;
use Artwork\Modules\Calendar\Services\EventPlanningCalendarService;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Calendar\Services\ShiftPlanService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use Artwork\Modules\Event\Events\BulkEventChanged;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Events\EventUpdated;
use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Events\RemoveEvent;
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
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\GlobalNotification\Services\GlobalNotificationService;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPresetGroup;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftListViewService;
use Artwork\Modules\Shift\Services\ShiftGroupService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Services\ShiftTimePresetService;
use Artwork\Modules\Event\Services\SubEventService;
use Artwork\Modules\Shift\Services\SingleShiftPresetService;
use Artwork\Modules\Task\Http\Resources\TaskDashboardResource;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Artwork\Modules\User\Services\WorkingHourService;
use Artwork\Modules\Worker\Services\WorkerService;
use Artwork\Modules\Worker\Services\WorkerShiftPlanService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
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
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class EventController extends Controller
{
    private const MAX_MULTI_CELL_TARGETS = 250;
    private const MAX_MULTI_CELL_SOURCE_EVENTS = 50;
    private const MAX_MULTI_CELL_DUPLICATES = 500;

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
        private readonly ShiftPlanService $shiftPlanService,
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
        protected readonly SingleShiftPresetService $singleShiftPresetService,
        private readonly GeneralSettingsService $generalSettingsService,
        protected GlobalQualificationService $globalQualificationService,
        protected ShiftGroupService $shiftGroupService,
        protected HelperService $helperService,
        private readonly ShiftListViewService $shiftListViewService,
        private readonly WorkingHourCacheService $workingHourCacheService,
        private readonly WorkerService $workerService,
        private readonly WorkerShiftPlanService $workerShiftPlanService,
        private readonly RoomRequestNotificationService $roomRequestNotificationService,
    ) {
    }


    public function redirectToCalendar(Event $event): \Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = $this->authManager->user();

        // get full calendar week of event
        $startOfWeek = Carbon::parse($event->start_time)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::parse($event->end_time)->endOfWeek(Carbon::SUNDAY);



        $user->userFilters()->calendarFilter()->first()->update([
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'event_type_ids' => null,
            'room_ids' => null,
            'area_ids' => null,
            'room_attribute_ids' => null,
            'room_category_ids' => null,
            'event_property_ids' => null,
            'craft_ids' => null,
        ]);

        return redirect()->route('events', [
            'highlightEventId' => $event->id
        ]);
    }

    public function redirectToCalendarByDay(string $day): \Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = $this->authManager->user();

        $startDate = Carbon::parse($day);
        $endDate = $startDate->copy()->addDays(7);

        $user->userFilters()->calendarFilter()->first()->update([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        return redirect()->route('events');
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
                                'shifts.users.globalQualifications',
                                'shifts.freelancer',
                                'shifts.freelancer.globalQualifications',
                                'shifts.serviceProvider',
                                'shifts.serviceProvider.globalQualifications',
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
        $isDailyView = (bool) $user->getAttribute('calendar_daily_view');

        if ($isDailyView) {
            $calendarFilterType = UserFilterTypes::CALENDAR_DAILY_FILTER->value;
            $userCalendarFilter   = $user->userFilters()->firstOrCreate(
                ['filter_type' => $calendarFilterType],
                ['start_date' => null, 'end_date' => null]
            );
            $userCalendarSettings = $user->getAttribute('daily_view_calendar_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->daily_view_calendar_settings()->create();
            }
        } else {
            $calendarFilterType = UserFilterTypes::CALENDAR_FILTER->value;
            $userCalendarFilter   = $user->userFilters()->firstOrCreate(
                ['filter_type' => $calendarFilterType],
                ['start_date' => null, 'end_date' => null]
            );
            $userCalendarSettings = $user->getAttribute('calendar_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->calendar_settings()->create();
            }
        }

        $isPlanning           = $request->boolean('isPlanning', false);

        // Abo/Shared Daten (leichtgewichtig lassen)
        $this->userService->shareCalendarAbo('calendar');

        // Datum bestimmen
        $dateRangeRequested = $request->filled(['start_date','end_date']);
        if ($dateRangeRequested) {
            // Ungültige Datums-Strings sollen Validierungsfehler statt 500 liefern
            $request->validate(['start_date' => 'date', 'end_date' => 'date']);
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate   = Carbon::parse($request->input('end_date'))->endOfDay();
        } else {
            [$startDate, $endDate] = $this->calendarDataService
                ->getCalendarDateRange($userCalendarSettings, $userCalendarFilter, $project);
        }

        // Sicherheitskappen für View-Spannen: Tagesansicht bis zu einem Monat
        // (leere Stunden kollabieren clientseitig, ~50k DOM-Knoten bei 31 Tagen gemessen ok)
        $calendarWarningText = '';
        if ($isDailyView && $startDate->diffInDays($endDate) > 30) {
            // 30 Tage Differenz = 31 Kalendertage inklusive — passend zur Meldung "31 Tage"
            $endDate = $startDate->copy()->addDays(30);
            $calendarWarningText = __('calendar.daily_view_info');

            $user->userFilters()->updateOrCreate(
                ['filter_type' => $calendarFilterType],
                ['end_date' => $endDate->format('Y-m-d')]
            );
        }

        if ($startDate->diffInDays($endDate) > (365 * 2)) {
            $endDate = $startDate->copy()->addYears(2);
            $calendarWarningText = __('calendar.calendar_limit_two_years');

            $user->userFilters()->updateOrCreate(
                ['filter_type' => $calendarFilterType],
                ['end_date' => $endDate->format('Y-m-d')]
            );
        }

        // Perioden/Monate (leichtgewichtig)
        $period = $this->calendarDataService->createCalendarPeriodDto($startDate, $endDate, $user, false, $isDailyView);

        $months = [];
        foreach ($period as $p) {
            if ($p->isExtraRow) {
                continue;
            }
            $date  = Carbon::parse($p->date);
            $key   = $date->format('m.Y');
            $months[$key] ??= [
                'first_day_in_period' => $date->format('Y-m-d'),
                'month' => $date->monthName,
                'year'  => $date->format('y'),
            ];
        }

        // **Rooms selbst sind leichtgewichtig** (id/name/admins/has_events Flag),
        // Events werden **lazy** geliefert (siehe 'calendar' Prop unten).
        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $startDate,
            $endDate,
            false // Calendar view: only events determine occupancy
        );

        // Transform Room models to RoomDTOs for frontend compatibility
        $roomDTOs = $rooms->map(fn($room) => new RoomDTO(
            id: $room->id,
            name: $room->name,
            has_events: $room->events_count > 0,
            admins: $room->admins->pluck('id')->toArray(),
            everyone_can_book: $room->everyone_can_book,
            requestable_by: $room->requestableBy->pluck('id')->toArray(),
        ));

        $dateValue = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];

        return Inertia::render('Calendar/Index', [
            'period'                 => $period,
            'months'                 => $months,
            'rooms'                  => $roomDTOs,
            'dateValue'              => $dateValue,
            'user_filters'           => $userCalendarFilter,
            'calendarWarningText'    => $calendarWarningText,
            'personalFilters' => fn () =>
            $this->filterService->getPersonalFilter($user, $calendarFilterType),
            'filterOptions'   => fn () => $this->filterService->getCalendarFilterDefinitions(),
            'eventsWithoutRoom' => fn () =>
             Event::query()->hasNoRoom()->get()->map(fn($event) =>
                 \Artwork\Modules\Calendar\DTO\EventWithoutRoomDTO::formModel(
                     $event,
                     $userCalendarSettings,
                     EventType::select(['id','name','abbreviation','hex_code'])->get()->keyBy('id')
                 )),
            'areas'            => fn () => $this->areaService->getAll(),
            'eventTypes'       => fn () => EventType::select(['id','name','abbreviation','hex_code'])->orderBy('name')->get(),
            'eventStatuses'    => fn () => EventStatus::orderBy('order')->get(),
            'event_properties' => fn () => EventProperty::all(),
            'first_project_tab_id' => fn () => $this->projectTabService->getDefaultOrFirstProjectTabId(),
            'first_project_calendar_tab_id' => fn () => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'first_project_shift_tab_id' => fn () => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),

            'projectNameUsedForProjectTimePeriod' => fn () =>
            $userCalendarSettings->getAttribute('time_period_project_id')
                ? $this->projectService->findById(
                    $userCalendarSettings->getAttribute('time_period_project_id')
                )->name
                : null,
            'filterType' => $calendarFilterType,
            'isDailyView' => $isDailyView,
            // Daten für Schicht-Karten + Schicht-Bearbeiten-Modal (nur bei aktivem "Schichten anzeigen")
            'shiftQualifications' => fn () => $userCalendarSettings?->work_shifts
                ? $this->shiftQualificationService->getAllOrderedByCreationDateAscending()
                : [],
            'globalQualifications' => fn () => $userCalendarSettings?->work_shifts
                ? $this->globalQualificationService->getAll()
                : [],
            'crafts' => fn () => $userCalendarSettings?->work_shifts
                ? Craft::query()
                    ->select(['id', 'name', 'abbreviation', 'color', 'universally_applicable', 'position'])
                    ->without(['craftShiftPlaner', 'craftInventoryPlaner'])
                    ->orderBy('position')
                    ->get()
                : [],
            'currentUserCrafts' => fn () => $userCalendarSettings?->work_shifts
                ? $this->getCurrentUserCrafts($user)
                : [],
            'shiftTimePresets' => fn () => $userCalendarSettings?->work_shifts
                ? $this->shiftTimePresetService->getAll()
                : [],
            'shiftGroups' => fn () => $userCalendarSettings?->work_shifts
                ? $this->shiftGroupService->getAllShiftGroups()
                : [],
        ]);
    }

    public function allEventsAPI(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $isDailyView = (bool) $user->getAttribute('calendar_daily_view');

        if ($isDailyView) {
            $userCalendarSettings = $user->getAttribute('daily_view_calendar_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->daily_view_calendar_settings()->create();
            }
        } else {
            $userCalendarSettings = $user->getAttribute('calendar_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->calendar_settings()->create();
            }
        }

        $isPlanning           = $request->boolean('isPlanning', false);

        if ($isPlanning) {
            $filterType = $isDailyView
                ? UserFilterTypes::PLANNING_DAILY_FILTER->value
                : UserFilterTypes::PLANNING_FILTER->value;
        } else {
            $filterType = $isDailyView
                ? UserFilterTypes::CALENDAR_DAILY_FILTER->value
                : UserFilterTypes::CALENDAR_FILTER->value;
        }
        $userCalendarFilter = $user->userFilters()->firstOrCreate(
            ['filter_type' => $filterType],
            ['start_date' => null, 'end_date' => null]
        );

        // Ungültige Datums-Strings sollen 422 statt 500 liefern
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate   = Carbon::parse($validated['end_date'])->endOfDay();

        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $startDate,
            $endDate,
            false // Calendar API: only events determine occupancy
        );

        $calendar = ($isPlanning
            ? $this->eventPlanningCalendarService
            : $this->eventCalendarService
        )->mapRoomsToContentForCalendar(
            ($isPlanning
                ? $this->eventPlanningCalendarService
                : $this->eventCalendarService
            )->filterRoomsEvents(
                $rooms,
                $userCalendarFilter,
                $startDate,
                $endDate,
                $userCalendarSettings
            ),
            $startDate,
            $endDate
        );

        return response()->json(['calendar' => $calendar->rooms]);
    }

    public function viewPlanningCalendar(Request $request, ?Project $project = null): Response
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $isDailyView = (bool) $user->getAttribute('calendar_daily_view');

        if ($isDailyView) {
            $planningFilterType = UserFilterTypes::PLANNING_DAILY_FILTER->value;
            $userCalendarFilter = $user->userFilters()->firstOrCreate(
                ['filter_type' => $planningFilterType],
                ['start_date' => null, 'end_date' => null]
            );
            $userCalendarSettings = $user->getAttribute('daily_view_calendar_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->daily_view_calendar_settings()->create();
            }
        } else {
            $planningFilterType = UserFilterTypes::PLANNING_FILTER->value;
            $userCalendarFilter = $user->userFilters()->firstOrCreate(
                ['filter_type' => $planningFilterType],
                ['start_date' => null, 'end_date' => null]
            );
            $userCalendarSettings = $user->getAttribute('calendar_settings');
        }

        $this->userService->shareCalendarAbo('calendar');

        [$startDate, $endDate] = $this->calendarDataService
                ->getCalendarDateRange($userCalendarSettings, $userCalendarFilter, $project);

        $calendarWarningText = '';

        // Tagesansicht bis zu einem Monat (wie im Standard-Kalender)
        if ($isDailyView && $startDate->diffInDays($endDate) > 30) {
            // 30 Tage Differenz = 31 Kalendertage inklusive — passend zur Meldung "31 Tage"
            $endDate = $startDate->copy()->addDays(30);
            $calendarWarningText = __('calendar.daily_view_info');
            $user->userFilters()->updateOrCreate([
                'filter_type' => $planningFilterType
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }


        if ($startDate->diffInDays($endDate) > (365 * 2)) {
            $endDate = $startDate->copy()->addYears(2);
            $calendarWarningText = __('calendar.calendar_limit_two_years');
            $user->userFilters()->updateOrCreate([
                'filter_type' => $planningFilterType
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }

        $period = $this->calendarDataService->createCalendarPeriodDto(
            $startDate,
            $endDate,
            $user,
            false,
            $isDailyView
        );

        $months = [];
        foreach ($period as $periodObject) {
            if ($periodObject->isExtraRow) {
                continue;
            }
            $date = Carbon::parse($periodObject->date);
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
            false // Planning calendar: only events determine occupancy
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

        // Transform Room models to RoomDTOs for frontend compatibility
        $roomDTOs = $rooms->map(fn($room) => new RoomDTO(
            id: $room->id,
            name: $room->name,
            has_events: $room->events_count > 0,
            admins: $room->admins->pluck('id')->toArray(),
            everyone_can_book: $room->everyone_can_book,
            requestable_by: $room->requestableBy->pluck('id')->toArray(),
        ));

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
            'rooms' => $roomDTOs,
            'calendar' => Inertia::always(fn() => $calendarData->rooms),
            'personalFilters' => Inertia::always(fn() => $this->filterService
                ->getPersonalFilter($user, $planningFilterType)),
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
            'filterType' => $planningFilterType,
            'isDailyView' => $isDailyView,
            // Daten für Schicht-Karten + Schicht-Bearbeiten-Modal (nur bei aktivem "Schichten anzeigen")
            'shiftQualifications' => fn () => $userCalendarSettings?->work_shifts
                ? $this->shiftQualificationService->getAllOrderedByCreationDateAscending()
                : [],
            'globalQualifications' => fn () => $userCalendarSettings?->work_shifts
                ? $this->globalQualificationService->getAll()
                : [],
            'crafts' => fn () => $userCalendarSettings?->work_shifts
                ? Craft::query()
                    ->select(['id', 'name', 'abbreviation', 'color', 'universally_applicable', 'position'])
                    ->without(['craftShiftPlaner', 'craftInventoryPlaner'])
                    ->orderBy('position')
                    ->get()
                : [],
            'currentUserCrafts' => fn () => $userCalendarSettings?->work_shifts
                ? $this->getCurrentUserCrafts($user)
                : [],
            'shiftTimePresets' => fn () => $userCalendarSettings?->work_shifts
                ? $this->shiftTimePresetService->getAll()
                : [],
            'shiftGroups' => fn () => $userCalendarSettings?->work_shifts
                ? $this->shiftGroupService->getAllShiftGroups()
                : [],
        ]);
    }

    public function shiftPlanEventAPI(Request $request): JsonResponse
    {
        // GET via axios.get(..., { params: ... }) => Query-String
        $projectId = $request->query('projectId'); // statt get()
        $project = !empty($projectId)
            ? $this->projectService->findById($projectId)
            : null;

        // boolean sauber auslesen (akzeptiert "1", "true", 1, true, "on", ...)
        // Optional: Wenn projectId gesetzt ist, ist es praktisch immer "Project View"
        $isInProjectView = $request->boolean('isInProjectView', !empty($projectId));

        /** @var User $user */
        $user = $this->authManager->user();
        $isDailyView = !$isInProjectView && (bool) $user->getAttribute('shift_plan_daily_view');

        if ($isDailyView) {
            $userCalendarSettings = $user->getAttribute('daily_view_calendar_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->daily_view_calendar_settings()->create();
            }
        } else {
            $userCalendarSettings = $user->getAttribute('calendar_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->calendar_settings()->create();
            }
        }

        $shiftFilterType = $isInProjectView
            ? UserFilterTypes::PROJECT_SHIFT_FILTER->value
            : ($isDailyView
                ? UserFilterTypes::SHIFT_DAILY_FILTER->value
                : UserFilterTypes::SHIFT_FILTER->value);

        $userCalendarFilter = $user->userFilters()->firstOrCreate(
            ['filter_type' => $shiftFilterType
            ],
            [
                'start_date' => null,
                'end_date' => null,
                'event_type_ids' => null,
                'room_ids' => null,
                'area_ids' => null,
                'room_attribute_ids' => null,
                'room_category_ids' => null,
                'event_property_ids' => null,
                'craft_ids' => null,
            ]
        );

        // Zeitraum aus Request respektieren
        $startDateParam = $request->query('start_date');
        $endDateParam   = $request->query('end_date');

        if (!empty($startDateParam) && !empty($endDateParam)) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate   = Carbon::parse($endDateParam)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->calendarDataService
                ->getCalendarDateRange($userCalendarSettings, $userCalendarFilter, $project);
        }

        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $startDate,
            $endDate,
            true,
            $project
        );

        $period = $this->calendarDataService->createCalendarPeriodDto(
            $startDate,
            $endDate,
            $user,
            true,
            $isDailyView
        );

        $filterResult = $this->shiftCalendarService->filterRoomsEventsAndShifts(
            $rooms,
            $userCalendarFilter,
            $startDate,
            $endDate,
            (bool) $project || $isDailyView,
            $project,
            true,
            $userCalendarSettings
        );
        $rooms = $filterResult['rooms'];

        $calendarData = $this->shiftCalendarService->mapRoomsToContentForCalendar(
            $rooms,
            $startDate,
            $endDate,
        );

        if ($userCalendarSettings->hide_unoccupied_days) {
            $result = $this->calendarDataService->hideUnoccupiedDays($calendarData, $period);
            $calendarData = $result['calendarData'];
            $period       = $result['period'];
        }

        return response()->json([
            'days' => $period,
            'shiftPlan' => $calendarData->rooms,
            'singleShiftPresets' => $this->singleShiftPresetService->getAllPresets(),
            'shiftGroupPresets' => ShiftPresetGroup::query()
                ->select(['id', 'name'])
                ->withCount('presets')
                ->with([
                    'presets' => function ($q) {
                        $q->select([
                            'single_shift_presets.id',
                            'single_shift_presets.name',
                            'single_shift_presets.start_time',
                            'single_shift_presets.end_time',
                            'single_shift_presets.break_duration',
                            'single_shift_presets.craft_id',
                            'single_shift_presets.description',
                        ])->with([
                            'craft:id,name,abbreviation,color',
                            'shiftsQualifications:id,name,icon,available',
                        ]);
                    }
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function shiftPlanMetaAPI(Request $request): JsonResponse
    {
        return response()->json($this->shiftPlanService->getMeta($request));
    }

    public function shiftPlanRoomAPI(Request $request): JsonResponse
    {
        if ($request->query('room_id') === null || $request->query('room_id') === '') {
            return response()->json(['error' => 'room_id required'], 422);
        }

        $result = $this->shiftPlanService->getRoomContent($request);
        if ($result === null) {
            return response()->json(['error' => 'Room not found or not in filter'], 404);
        }

        return response()->json($result);
    }

    public function shiftPlanRoomsBatchAPI(Request $request): JsonResponse
    {
        return response()->json($this->shiftPlanService->getAllRoomsContent($request));
    }

    public function viewShiftPlan(?Project $project = null): Response
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $isDailyView = (bool) $user->getAttribute('shift_plan_daily_view');

        if ($isDailyView) {
            $shiftFilterType = UserFilterTypes::SHIFT_DAILY_FILTER->value;
            $userCalendarFilter = $user->userFilters()->firstOrCreate(
                ['filter_type' => $shiftFilterType],
                ['start_date' => null, 'end_date' => null]
            );
            $userCalendarSettings = $user->getAttribute('shift_plan_daily_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->shift_plan_daily_settings()->create();
            }
        } else {
            $shiftFilterType = UserFilterTypes::SHIFT_FILTER->value;
            $userCalendarFilter = $user->userFilters()->firstOrCreate(
                ['filter_type' => $shiftFilterType],
                ['start_date' => null, 'end_date' => null]
            );
            $userCalendarSettings = $user->getAttribute('shift_plan_settings');
            if ($userCalendarSettings === null) {
                $userCalendarSettings = $user->shift_plan_settings()->create();
            }
        }

        $renderViewName = 'Shifts/ShiftPlan';
        $this->userService->shareCalendarAbo('shiftCalendar');
        $this->singleShiftPresetService->shareSingleShiftPresets();


        [$startDate, $endDate] = $this->calendarDataService
            ->getCalendarDateRange($userCalendarSettings, $userCalendarFilter, $project);
        $calendarWarningText = '';

        // Ensure start_date <= end_date (can happen when start_date was null and defaulted to today)
        if ($startDate->greaterThan($endDate)) {
            $endDate = $startDate->copy()->addDays($isDailyView ? 0 : 6);
            $user->userFilters()->updateOrCreate([
                'filter_type' => $shiftFilterType
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }

        if ($isDailyView && $startDate->diffInDays($endDate) > 7) {
            $endDate = $startDate->copy()->addDays(7);
            $calendarWarningText = __('calendar.daily_view_info');
            $user->userFilters()->updateOrCreate([
                'filter_type' => $shiftFilterType
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }

        // only allow six months in shift plan view
        if ($startDate->diffInDays($endDate) > 183) {
            $endDate = $startDate->copy()->addMonths(6);
            $calendarWarningText = __('calendar.calendar_limit_six_months');
            $user->userFilters()->updateOrCreate([
                'filter_type' => $shiftFilterType
            ], [
                'end_date' => $endDate->format('Y-m-d')
            ]);
        }


        $dateValue = [
            $startDate ? $startDate->format('Y-m-d') : null,
            $endDate ? $endDate->format('Y-m-d') : null
        ];

        if ($isDailyView) {
            $renderViewName = 'Shifts/ShiftPlanDailyView';
        }

        return Inertia::render($renderViewName, [
            'history' => [],
            'crafts' => Craft::query()
                ->select(['id', 'name', 'abbreviation', 'color', 'universally_applicable', 'position'])
                ->with([
                    'users',
                    'managingUsers',
                    'managingFreelancers',
                    'managingServiceProviders',
                ])
                ->without(['craftShiftPlaner', 'craftInventoryPlaner'])
                ->orderBy('position')
                ->get(),
            'eventTypes' => EventType::all(),
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'event_properties' => EventProperty::all(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'personalFilters' => $this->filterService->getPersonalFilter($user, $shiftFilterType),
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
            'projectId' => $project->id ?? null,
            'shiftPlanWorkerSortEnums' => array_map(
                static function (ShiftPlanWorkerSortEnum $enum): string {
                    return $enum->name;
                },
                ShiftPlanWorkerSortEnum::cases()
            ),
            'useFirstNameForSort' => (new ShiftSettings())->use_first_name_for_sort,
            'userShiftPlanShiftQualificationFilters' => $user->getAttribute('show_qualifications'),
            'currentUserCrafts' => $this->getCurrentUserCrafts($user),
            'shiftTimePresets' => $this->shiftTimePresetService->getAll(),
            'shiftGroupPresets' => ShiftPresetGroup::query()
                ->select(['id', 'name'])
                ->withCount('presets')
                ->with([
                    'presets' => function ($q) {
                        $q->select([
                            'single_shift_presets.id',
                            'single_shift_presets.name',
                            'single_shift_presets.start_time',
                            'single_shift_presets.end_time',
                            'single_shift_presets.break_duration',
                            'single_shift_presets.craft_id',
                            'single_shift_presets.description',
                        ])->with([
                            'craft:id,name,abbreviation,color',
                            'shiftsQualifications:id,name,icon,available', // pivot(quantity) kommt automatisch mit
                        ]);
                    }
                ])
                ->orderBy('name')
                ->get(),
            'calendarWarningText' => $calendarWarningText,
            'globalQualifications' => $this->globalQualificationService->getAll(),
            'shiftGroups' => $this->shiftGroupService->getAllShiftGroups(),
            'filterType' => $shiftFilterType,
            'isDailyView' => $isDailyView,
        ]);
    }

    public function viewShiftPlanListView(): Response
    {
        /** @var User $user */
        $user = $this->authManager->user();

        $shiftFilterType = UserFilterTypes::SHIFT_LIST_VIEW_FILTER->value;
        $userCalendarFilter = $user->userFilters()->firstOrCreate(
            ['filter_type' => $shiftFilterType],
            [
                'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            ]
        );

        $listViewSettings = $user->shift_list_view_settings;
        if ($listViewSettings === null) {
            $listViewSettings = $user->shift_list_view_settings()->create();
        }

        $startDate = $userCalendarFilter->start_date
            ? Carbon::parse($userCalendarFilter->start_date)
            : Carbon::now()->startOfMonth();
        $endDate = $userCalendarFilter->end_date
            ? Carbon::parse($userCalendarFilter->end_date)
            : Carbon::now()->endOfMonth();

        $calendarWarningText = '';
        if ($startDate->diffInDays($endDate) > 183) {
            $endDate = $startDate->copy()->addMonths(6);
            $calendarWarningText = __('calendar.calendar_limit_six_months');
            $user->userFilters()->updateOrCreate(
                ['filter_type' => $shiftFilterType],
                ['end_date' => $endDate->format('Y-m-d')]
            );
        }

        $dateValue = [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ];

        $groupedShifts = $this->shiftListViewService->getGroupedShifts(
            $startDate,
            $endDate,
            $listViewSettings,
            $userCalendarFilter
        );

        return Inertia::render('Shifts/ShiftPlanListView', [
            'groupedShifts' => $groupedShifts,
            'dateValue' => $dateValue,
            'listViewSettings' => $listViewSettings,
            'user_filters' => $userCalendarFilter,
            'crafts' => Craft::with([
                'users',
                'freelancers',
                'serviceProviders',
                'qualifications:id,name,icon',
            ])->get(['id', 'name', 'abbreviation', 'color', 'position', 'universally_applicable']),
            'eventTypes' => EventType::select(['id', 'name', 'abbreviation', 'hex_code'])->get(),
            'filterOptions' => $this->filterService->getCalendarFilterDefinitions(),
            'personalFilters' => $this->filterService->getPersonalFilter($user, $shiftFilterType),
            'shiftQualifications' => $this->shiftQualificationService->getAllOrderedByCreationDateAscending(),
            'firstProjectShiftTabId' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),
            'filterType' => $shiftFilterType,
            'calendarWarningText' => $calendarWarningText,
            'rooms' => Room::select(['id', 'name', 'position', 'area_id'])->get(),
            'shiftTimePresets' => $this->shiftTimePresetService->getAll(),
            'shiftGroups' => $this->shiftGroupService->getAllShiftGroups(),
            'globalQualifications' => $this->globalQualificationService->getAll(),
            'currentUserCrafts' => $this->getCurrentUserCrafts($user),
            'eventStatuses' => EventStatus::orderBy('order')->get(),
        ]);
    }

    public function updateShiftListViewSettings(User $user, Request $request, UserService $userService): void
    {
        $this->authorize('updateOwnPreferences', $user);

        // Ansichtsübergreifendes Setting (users-Spalte); beim Einschalten aus der
        // Listenansicht übernehmen alle Ansichten deren Zeitraum
        if ($request->has('share_calendar_date')) {
            $userService->updateShareCalendarDateSetting(
                $user,
                $request->boolean('share_calendar_date'),
                UserFilterTypes::SHIFT_LIST_VIEW_FILTER->value
            );
        }

        $user->shift_list_view_settings()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'show_qualifications',
                'shift_notes',
                'show_shift_group_tag',
                'show_fully_staffed_shifts',
                'detailed_shift_overview',
                'show_appointments',
                'group_by_shift_groups',
                'hide_shift_row',
            ])
        );
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
        $today = $now->format('Y-m-d');
        $shiftsOfDay = $user
            ->shifts()
            ->whereDate(
                'shifts.start_date',
                $today
            )->with(['event','event.project','event.room', 'event.event_type', 'room', 'project'])->get();

        $individualTimesOfDay = $user
            ->individualTimes()
            ->whereDate('start_date', '<=', $today)
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->with(['series'])
            ->get();

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
            $event = Event::with([
                'room',
                'creator',
                'project',
                'project.managerUsers',
                'project.status',
                'event_type',
                'eventStatus',
                'eventProperties',
                'shifts',
                'shifts.craft',
                'shifts.users',
                'shifts.freelancer',
                'shifts.serviceProvider',
                'shifts.shiftsQualifications',
                'subEvents.event',
                'subEvents.event.room',
                'series',
            ])->find(request('eventId'));
        }

        $historyObjects = [];

        // reload functions
        if (request('showHistory')) {
            if (request('historyType') === 'project') {
                $project = Project::find(request('modelId'));
                if ($project !== null) {
                    $historyObjects = array_merge(
                        $historyObjects,
                        $this->changeService->historyForFrontend($project)
                    );
                }
            }

            if (request('historyType') === 'event') {
                $event = Event::find(request('modelId'));
                if ($event !== null) {
                    $historyObjects = array_merge(
                        $historyObjects,
                        $this->changeService->historyForFrontend($event)
                    );
                }
            }
        }
        return inertia('Dashboard', [
            'tasks' => TaskDashboardResource::collection($tasks)->resolve(),
            'users_day_services_of_day' => $user->dayServices()->wherePivot('date', $now)->get(),
            'shiftsOfDay' => $shiftsOfDay,
            'individualTimesOfDay' => $individualTimesOfDay,
            'todayDate' => $todayDate,
            'eventsOfDay' => $userEvents,
            'globalNotification' => $globalNotificationService->getGlobalNotificationEnrichedByImageUrl(),
            // Only the first page is shipped with the dashboard; further pages are loaded on
            // demand via the notifications.today endpoint so a user with thousands of
            // notifications does not bloat the payload. Keep perPage (5) in sync with Dashboard.vue.
            'notificationCount' => $notification->count(),
            'notificationOfToday' => $notification->take(5)->get(),
            'event' => $event !== null ? new CalendarEventResource($event) : null,
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'rooms' => Room::select(['id', 'name', 'area_id', 'order'])->get(),
            'projects' => Project::select(['id', 'name'])->get(),
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

        if ($request->filled('projectId')) {
            $this->authorize('view', Project::query()->findOrFail($request->integer('projectId')));
        }
        if ($request->filled('projectName')) {
            $this->authorize('create', Project::class);
        }

        // Server-side enforcement: verify the user can actually book or request for this room
        $user = auth()->user();
        $roomId = $request->get('roomId');
        $isOption = $request->booleanValue('isOption');

        if (!$roomId && !$user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            $canCreateWithoutRoom = $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
                ($request->booleanValue('isPlanning') &&
                    $user->can(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value));

            if (!$canCreateWithoutRoom) {
                if ($user->can(PermissionEnum::EVENT_REQUEST->value)) {
                    throw ValidationException::withMessages([
                        'roomId' => __('A room is required for a room request.'),
                    ]);
                }

                abort(403);
            }
        }

        if ($roomId && !$user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            $room = Room::find($roomId);
            if ($room) {
                $isRoomAdmin = $room->admins()->where('user_id', $user->id)->exists();
                $canRequestForRoom = $room->requestableBy()->where('user_id', $user->id)->exists();
                $hasGlobalCreate = $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
                $hasGlobalRequest = $user->can(PermissionEnum::EVENT_REQUEST->value);
                $isPlanning = $request->booleanValue('isPlanning');
                $canPlanFixed = $isPlanning && $user->can(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value);
                $canBookDirectly = $hasGlobalCreate || $canPlanFixed || $isRoomAdmin || $room->everyone_can_book;
                $canRequest = $hasGlobalCreate ||
                    $hasGlobalRequest ||
                    $isRoomAdmin ||
                    $canRequestForRoom ||
                    $room->everyone_can_book;

                // A stale or manipulated client must never turn request-only access into a direct booking.
                if (!$isOption && !$canBookDirectly) {
                    if (!$canRequest) {
                        abort(403);
                    }

                    $isOption = true;
                    $request->merge(['isOption' => true]);
                }

                // Request booking (isOption=true): must have global request permission or room-specific can_request
                if ($isOption && !$canRequest) {
                    abort(403);
                }
            }
        }

        /** @var Event $firstEvent */
        $firstEvent = Event::create($request->data());
        $firstEvent->eventProperties()->sync($request->input('event_properties', []));
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

        // Raumanfragen zu geplanten Terminen werden erst beim Umstellen auf einen
        // "richtigen" Termin verschickt (siehe EventVerificationService::confirmEvent)
        if ($request->isOption && !$firstEvent->is_planning) {
            $this->roomRequestNotificationService->notifyRoomAdmins($firstEvent);
        }

        broadcast(new OccupancyUpdated())->toOthers();

        if ($request->booleanValue('showProjectPeriodInCalendar')) {
            return $this->redirector->back();
        }

        broadcast(new EventCreated(
            $firstEvent->load(['event_type', 'project']),
            $firstEvent->room_id
        ));

        return new CalendarEventResource($firstEvent);
    }

    private function createSeriesEvent(
        $startDate,
        $endDate,
        $request,
        $series,
        $projectId,
        ?Event $sourceEvent = null
    ): Event {
        $event = Event::create([
            'name' => $sourceEvent?->name ?? $request->title,
            'eventName' => $sourceEvent?->eventName ?? $request->eventName,
            'description' => $sourceEvent?->description ?? $request->description,
            'start_time' => $startDate,
            'end_time' => $endDate,
            'admission_time' => $sourceEvent?->admission_time ?? $request->admissionTime,
            'occupancy_option' => $sourceEvent?->occupancy_option ?? $request->isOption,
            'audience' => $sourceEvent?->audience ?? $request->audience,
            'is_loud' => $sourceEvent?->is_loud ?? $request->isLoud,
            'event_type_id' => $sourceEvent?->event_type_id ?? $request->eventTypeId,
            'event_status_id' => $sourceEvent?->event_status_id ?? $request->eventStatusId,
            'room_id' => $sourceEvent?->room_id ?? $request->roomId,
            'user_id' => Auth::id(),
            'project_id' => $projectId ?: null,
            'is_series' => true,
            'series_id' => $series->id,
            'allDay' => $sourceEvent?->allDay ?? $request->allDay,
            'is_planning' => $sourceEvent?->is_planning ?? $request->get('isPlanning', false),
        ]);
        $event->eventProperties()
            ->sync($request->input('event_properties', []));

        if ($event->occupancy_option && !$event->is_planning) {
            $this->roomRequestNotificationService->notifyRoomAdmins($event);
        }

        broadcast(new EventCreated(
            $event->fresh(),
            $event->room_id
        ));

        return $event;
    }

    public function commitShifts(Request $request): void
    {
        [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear(
            $request->week_number,
            $request->year
        );

        // Mehrfachauswahl im Modal: craft_ids (Array) ODER einzelnes craft_id (Altbestand).
        $craftIds = $request->input('craft_ids');
        if (! is_array($craftIds) || $craftIds === []) {
            $craftIds = [$request->get('craft_id')];
        }

        foreach (array_unique(array_map('intval', array_filter($craftIds))) as $craftId) {
            $this->shiftService->commitShiftsByDate(
                $start,
                $end,
                $craftId,
                $request->filled('week_number') ? (int) $request->week_number : null,
                $request->filled('year') ? (int) $request->year : null
            );
        }
    }

    public function changeCommitShifts(Request $request, Shift $shift): void
    {
        $committed = $request->boolean('commit');

        $shift->update([
            'is_committed' => $committed,
            'committing_user_id' => $committed ? Auth::id() : null,
        ]);

        // is_committed ist nicht in logOnly — Einzel-Toggle explizit loggen.
        $this->shiftService->logSingleCommitActivity($shift, $committed);
    }


    private function adjoiningRoomsCheck(EventStoreRequest $request, $event): void
    {
        $joiningEvents = $this->collisionService->adjoiningCollision($request);
        foreach ($joiningEvents as $joiningEvent) {
            foreach ($joiningEvent as $conflict) {
                $user = User::find($conflict->user_id);
                if ($user === null || $user->id === Auth::id()) {
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
            'id' => Str::uuid()->toString(),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $room = $event->room;
        $project = $event->project;
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room?->name,
                'href' => $room ? route('rooms.show', $room->id) : null
            ],
            2 => [
                'type' => 'string',
                'title' =>  ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
            'id' => Str::uuid()->toString(),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $room = $event->room;
        $project = $event->project;
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room?->name,
                'href' => $room ? route('rooms.show', $room->id) : null
            ],
            2 => [
                'type' => 'string',
                'title' =>  ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
                'id' => Str::uuid()->toString(),
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
                    'title' => $room?->name,
                    'href' => $room ? route('rooms.show', $room->id) : null
                ],
                2 => [
                    'type' => 'string',
                    'title' =>  ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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

        $shouldAcceptRoomRequest = $event->occupancy_option &&
            $request->has('isOption') &&
            !$request->booleanValue('isOption');

        if ($shouldAcceptRoomRequest) {
            $this->authorize('answerRoomRequest', $event);
        }
        if ($request->filled('projectId') && $request->integer('projectId') !== $event->project_id) {
            $this->authorize('view', Project::query()->findOrFail($request->integer('projectId')));
        }
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

                $this->notificationService->setRoomId($event->room_id);
                $this->notificationService->setEventId($event->id);
                $this->notificationService->setButtons(['answerDialog']);
                foreach ($projectManagers as $projectManager) {
                    if ($projectManager->id === $event->user_id) {
                        continue;
                    }
                    $notificationTitle = __('notification.event.admin_message', [], $projectManager->language);
                    $broadcastMessage = [
                        'id' => Str::uuid()->toString(),
                        'type' => 'success',
                        'message' => $notificationTitle
                    ];

                    $event->save();
                    $notificationDescription = [
                        1 => [
                            'type' => 'link',
                            'title' => $room?->name,
                            'href' => $room ? route('rooms.show', $room->id) : null
                        ],
                        2 => [
                            'type' => 'string',
                            'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
                $notificationTitle = __('notification.event.admin_message', [], $event->creator?->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];

                //$this->eventService->save($event);
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room?->name,
                        'href' => $room ? route('rooms.show', $room->id) : null
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
                if ($event->creator !== null) {
                    $this->notificationService->setNotificationTo($event->creator);
                    $this->notificationService->createNotification();
                }
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
                if ($projectManager->id === $event->user_id) {
                    continue;
                }
                $notificationTitle = __('notification.event.room_change_confirmed', [], $projectManager->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room?->name,
                        'href' => $room ? route('rooms.show', $room->id) : null
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
            $notificationTitle = __('notification.event.room_change_confirmed', [], $event->creator?->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room?->name,
                    'href' => $room ? route('rooms.show', $room->id) : null
                ],
                2 => [
                    'type' => 'string',
                    'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
            if ($event->creator !== null) {
                $this->notificationService->setNotificationTo($event->creator);
                $this->notificationService->createNotification();
            }
        }

        $oldEventDescription   = $event->description;
        $oldEventRoom          = $event->room_id;
        $oldEventProject       = $event->project_id;
        $oldEventName          = $event->eventName;
        $oldEventType          = $event->event_type_id;
        $oldEventStartDate     = $event->start_time;
        $oldEventEndDate       = $event->end_time;
        $oldEventAdmissionTime = $event->admission_time;
        $oldEventPropertyIds   = $event->getAttribute('eventProperties')->map(
            fn (EventProperty $eventProperty) => $eventProperty->getAttribute('id')
        )->all();

        $data = $request->data();

        // remove is_series and series_id from data to prevent overwriting
        unset($data['is_series'], $data['series_id']);
        if (!$request->has('isOption') || $shouldAcceptRoomRequest) {
            unset($data['occupancy_option']);
        }
        $event->fill($data);
        $event->eventProperties()->sync(($newEventPropertyIds = $request->input('event_properties', [])));
        $this->eventService->save($event);

        if ($shouldAcceptRoomRequest) {
            $this->acceptEvent($request, $event);
        }

        // Projekt ggf. anlegen & zuordnen (dein Original)
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

        // If room changed and new room requires approval, create a room request notification
        $roomRequestNotificationSent = false;
        if ($event->room_id && $oldEventRoom !== $event->room_id) {
            $newRoom = Room::find($event->room_id);
            if ($newRoom && !$newRoom->everyone_can_book) {
                $user = Auth::user();
                $isAdmin = $user->hasRole('artwork admin');
                $hasGlobalCreate = $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
                $isRoomAdmin = $newRoom->admins()->where('user_id', $user->id)->exists();

                if (!$isAdmin && !$hasGlobalCreate && !$isRoomAdmin) {
                    $event->update([
                        'occupancy_option' => true,
                        'declined_room_id' => null,
                        'accepted' => false,
                    ]);
                    // Bei geplanten Terminen geht die Raumanfrage erst beim Umstellen
                    // auf einen "richtigen" Termin raus
                    if (!$event->is_planning) {
                        $this->roomRequestNotificationService->notifyRoomAdmins($event);
                    }
                    $roomRequestNotificationSent = true;
                }
            }
        }

        // Update existing room request notifications if event details changed while request is still pending
        if (!$roomRequestNotificationSent && !$event->is_planning && $event->occupancy_option && $event->room_id) {
            $this->roomRequestNotificationService->notifyRoomAdmins($event);
        }

        $newEventDescription = $event->description;
        $newEventRoom        = $event->room_id;
        $newEventProject     = $event->project_id;
        $newEventName        = $event->eventName;
        $newEventType        = $event->event_type_id;
        $newEventStartDate   = $event->start_time;
        $newEventEndDate     = $event->end_time;

        $this->checkShortDescriptionChanges($event->id, $oldEventDescription, $newEventDescription);
        $this->checkRoomChanges($event->id, $oldEventRoom, $newEventRoom);
        $this->checkProjectChanges($event->id, $oldEventProject, $newEventProject);
        $this->checkEventNameChanges($event->id, $oldEventName, $newEventName);
        $this->checkEventTypeChanges($event->id, $oldEventType, $newEventType);
        $this->checkDateChanges($event->id, $oldEventStartDate, $newEventStartDate, $oldEventEndDate, $newEventEndDate);
        $this->checkAdmissionTimeChanges($event->id, $oldEventAdmissionTime, $event->admission_time);
        $this->checkEventPropertyChanges($event->id, $oldEventPropertyIds, $newEventPropertyIds);

        $this->createEventScheduleNotification($event);

        $oldEventStartDateDays = Carbon::create($oldEventStartDate);
        $oldEventEndDateDays   = Carbon::create($oldEventEndDate);
        $newEventStartDateDays = Carbon::parse($newEventStartDate);
        $newEventEndDateDays   = Carbon::parse($newEventEndDate);

        $diffStartDays    = $oldEventStartDateDays->diffInDays($newEventStartDateDays, false);
        $diffEndDays      = $oldEventEndDateDays->diffInDays($newEventEndDateDays, false);
        $diffStartMinutes = $oldEventStartDateDays->diffInRealMinutes($newEventStartDateDays, false);
        $diffEndMinutes   = $oldEventEndDateDays->diffInRealMinutes($newEventEndDateDays, false);

        if ($request->allSeriesEvents && $event->is_series) {
            $seriesEvents = Event::where('series_id', $event->series_id)->get();
            foreach ($seriesEvents as $seriesEvent) {
                if ($seriesEvent->id === $event->id) {
                    continue;
                }

                $startDay = Carbon::create($seriesEvent->start_time)->addDays($diffStartDays)->format('Y-m-d');
                $endDay   = Carbon::create($seriesEvent->end_time)->addDays($diffEndDays)->format('Y-m-d');

                $startTime = Carbon::create($seriesEvent->start_time)->addMinutes($diffStartMinutes)->format('H:i:s');
                $endTime   = Carbon::create($seriesEvent->end_time)->addMinutes($diffEndMinutes)->format('H:i:s');

                $seriesEvent->update([
                    'name'         => $event->name,
                    'eventName'    => $event->eventName,
                    'description'  => $event->description,
                    'occupancy_option' => $event->occupancy_option,
                    'audience'     => $event->audience,
                    'is_loud'      => $event->is_loud,
                    'event_type_id' => $event->event_type_id,
                    'room_id'      => $event->room_id,
                    'project_id'   => $event->project_id,
                    'start_time'   => $startDay . ' ' . $startTime,
                    'end_time'     => $endDay . ' ' . $endTime,
                ]);
            }
        }

        DB::transaction(function () use ($request, $event): void {
            $this->handleSeriesOnUpdate($request, $event);
        });

        $shifts = Shift::where('event_id', $event->id)->get();
        foreach ($shifts as $shift) {
            $startDay = Carbon::create($shift->start_date)->addDays($diffStartDays)->format('Y-m-d');
            $endDay   = Carbon::create($shift->end_date)->addDays($diffEndDays)->format('Y-m-d');

            $this->workingHourCacheService->forgetForShift($shift);

            // event_start_day/event_end_day mitziehen (Scopes, ShiftCountService und
            // Konflikt-Checks lesen diese Felder) und über ShiftService::save speichern,
            // damit individuelle Pivot-Zeiten (shift_workers.start_date/end_date)
            // synchronisiert und Änderungen an committed Schichten geloggt werden.
            $shift->fill([
                'start_date' => $startDay,
                'end_date'   => $endDay,
                'event_start_day' => Carbon::create($event->start_time)->format('Y-m-d'),
                'event_end_day' => Carbon::create($event->end_time)->format('Y-m-d'),
            ]);
            app(\Artwork\Modules\Shift\Services\ShiftService::class)->save($shift);
        }

        $this->craftInventoryItemEventService->updateEventTimesInInventory($event);

        // Projektzuordnungen (Re-Materialisierung/Auflösung bei Zeitraum-Änderung)
        // laufen zentral über den ProjectDayAssignmentEventObserver.

        broadcast(new EventCreated($event->fresh(), $event->fresh()->room_id));
    }

    private function createEventScheduleNotification(Event $event): void
    {
        if (!empty($event->project)) {
            foreach ($event->project->users->all() as $eventUser) {
                $this->schedulingService->create($eventUser->id, 'EVENT_CHANGES', 'EVENT', $event->id);
            }
        } elseif ($event->creator) {
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

        // Keine Benachrichtigung an Raumadmins zu geplanten Terminen - die Anfrage
        // erreicht sie erst beim Umstellen auf einen "richtigen" Termin
        if ($event->is_planning) {
            return;
        }

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
        $this->notificationService->setButtons(['show_in_calendar', 'accept', 'decline']);
        if ($admins->count() > 0) {
            foreach ($admins as $admin) {
                $notificationTitle = __('notification.event.new_message', [], $admin->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room?->name,
                        'href' => $room ? route('rooms.show', $room->id) : null
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
            // Raum kann gelöscht/null sein – dann gibt es keinen Empfänger für die Anfrage
            $user = $room ? User::find($room->user_id) : null;
            if ($user === null) {
                return;
            }
            $notificationTitle = __('notification.event.new_message', [], $user->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room?->name,
                    'href' => $room ? route('rooms.show', $room->id) : null
                ],
                2 => [
                    'type' => 'string',
                    'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
        $this->authorize('answerRoomRequest', $event);

        $updated = Event::query()
            ->whereKey($event->id)
            ->where('occupancy_option', true)
            ->whereNotNull('room_id')
            ->update(['occupancy_option' => false]);

        abort_if($updated === 0, 409, 'This room request has already been answered.');
        $event->refresh();

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Event::class)
                ->setModelId($event->id)
                ->setTranslationKey('Room confirmed')
        );

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
            if ($projectManager->id === $event->user_id) {
                continue;
            }
            $notificationTitle = __('notification.event.room_request_accept', [], $projectManager->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room?->name,
                    'href' => $room ? route('rooms.show', $room->id) : null
                ],
                2 => [
                    'type' => 'string',
                    'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
        $notificationTitle = __('notification.event.room_request_accept', [], $event->creator?->language);
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room?->name,
                'href' => $room ? route('rooms.show', $room->id) : null
            ],
            2 => [
                'type' => 'string',
                'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
        if ($event->creator !== null) {
            $this->notificationService->setNotificationTo($event->creator);
            $this->notificationService->createNotification();
        }

        /** @var User $currentUser */
        $currentUser = $this->authManager->user();
        $this->notificationService->updateRoomRequestNotificationStatus($event->id, 'accepted', $currentUser);

        broadcast(new EventUpdated($event->fresh(), $event->room_id));

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function declineEvent(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('answerRoomRequest', $event);

        $projectManagers = [];
        $roomId = $event->room_id;
        $room = $event->room()->first();
        $project = $event->project()->first();
        if (!empty($project)) {
            $projectManagers = $project->managerUsers()->get();
        }
        $updated = Event::query()
            ->whereKey($event->id)
            ->where('occupancy_option', true)
            ->where('room_id', $roomId)
            ->update([
                'accepted' => false,
                'occupancy_option' => false,
                'declined_room_id' => $roomId,
                'room_id' => null,
            ]);

        abort_if($updated === 0, 409, 'This room request has already been answered.');
        $event->refresh();

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
                if ($projectManager->id === $event->user_id) {
                    continue;
                }
                $notificationTitle = __('notification.event.admin_message', [], $projectManager->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'link',
                        'title' => $room?->name,
                        'href' => $room ? route('rooms.show', $room->id) : null
                    ],
                    2 => [
                        'type' => 'string',
                        'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
            $notificationTitle = __('notification.event.admin_message', [], $event->creator?->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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
                    'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
            if ($event->creator !== null) {
                $this->notificationService->setNotificationTo($event->creator);
                $this->notificationService->createNotification();
            }
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
            if ($projectManager->id === $event->user_id) {
                continue;
            }
            $notificationTitle = __('notification.event.room_request_declined', [], $projectManager->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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
                    'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
        $notificationTitle = __('notification.event.room_request_declined', [], $event->creator?->language);
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
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
                'title' => ($event->event_type?->name ?? '') . ', ' . $event->eventName,
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
        if ($event->creator !== null) {
            $this->notificationService->setNotificationTo($event->creator);
            $this->notificationService->createNotification();
        }

        /** @var User $currentUser */
        $currentUser = $this->authManager->user();
        $this->notificationService->updateRoomRequestNotificationStatus($event->id, 'declined', $currentUser);

        broadcast(new EventCreated(
            $event,
            $roomId
        ));

        return Redirect::back();
    }

    /**
     * Serie beim Bearbeiten setzen/aktualisieren:
     * - Serie neu anlegen, wenn vorher keine war und is_series=true.
     * - Bei bestehender Serie: bei Turnus-/Ende-Änderung alle zukünftigen Instanzen
     *   (ab dem bearbeiteten Event) löschen und gemäß neuem Plan neu erzeugen.
     * - Am Bearbeitungstag wird KEIN zusätzlicher Termin erzeugt.
     */
    protected function handleSeriesOnUpdate(Request $request, Event $event): void
    {
        $wantsSeries   = (bool) $request->boolean('is_series') ?? false;
        $newFrequency  = $request->input('seriesFrequency');   // 1,2,3,4 (daily/weekly/biweekly/monthly)
        $newSeriesEnd  = $request->input('seriesEndDate');     // z.B. '2025-12-31'

        // Wenn der Nutzer nichts zu Serie/Ende/Frequenz übermittelt, nichts tun.
        if ($wantsSeries === false && !$event->is_series) {
            return;
        }

        //dd($request->all(), $wantsSeries, $event->is_series, $newFrequency, $newSeriesEnd);

        $eventStart = Carbon::parse($event->start_time)->setTimezone(config('app.timezone'));
        $eventEnd   = Carbon::parse($event->end_time)->setTimezone(config('app.timezone'));

        // FALL A: vorher KEINE Serie -> jetzt Serie aktivieren
        if (!$event->is_series && $wantsSeries) {
            if (empty($newFrequency) || empty($newSeriesEnd)) {
                // Ohne Frequency/Ende können wir keine Serie sinnvoll erzeugen.
                return;
            }

            /** @var SeriesEvents $series */
            $series = SeriesEvents::create([
                'frequency_id' => (int) $newFrequency,
                'end_date'     => $newSeriesEnd,
            ]);

            $event->update([
                'is_series' => true,
                'series_id' => $series->id,
            ]);

            $endSeriesDateExclusive = Carbon::parse($newSeriesEnd)->addDay()->startOfDay();

            // Fortlaufende Erzeugung ab NÄCHSTER Instanz; am Bearbeitungstag nichts erzeugen.
            $cursorStart = $eventStart->copy();
            $cursorEnd   = $eventEnd->copy();
            [$nextStart, $nextEnd] = $this->generateNextOccurrence($cursorStart, $cursorEnd, (int) $newFrequency);

            while ($nextEnd < $endSeriesDateExclusive) {
                $this->createSeriesEvent($nextStart->copy(), $nextEnd->copy(), $request, $series, $event->project_id, $event);
                [$nextStart, $nextEnd] = $this->generateNextOccurrence($nextStart, $nextEnd, (int) $newFrequency);
            }

            return;
        }

        // FALL B: Serie existiert bereits – prüfen, ob Turnus oder Enddatum geändert wurde
        if ($event->is_series && $wantsSeries) {
            /** @var SeriesEvents|null $series */
            $series = SeriesEvents::find($event->series_id);
            if (!$series) {
                return;
            }

            $oldFrequency = (int) $series->frequency_id;
            $oldEnd       = Carbon::parse($series->end_date)->startOfDay();
            $freq         = (int) ($newFrequency ?: $oldFrequency);
            $seriesEndStr = $newSeriesEnd ?: $oldEnd->toDateString();

            $frequencyChanged = $freq !== $oldFrequency;
            $endDateChanged   = $seriesEndStr !== $oldEnd->toDateString();

            // Wenn weder Turnus noch Enddatum geändert wurde, Serie nicht neu generieren
            if (!$frequencyChanged && !$endDateChanged) {
                return;
            }

            $series->update([
                'frequency_id' => $freq,
                'end_date'     => $seriesEndStr,
            ]);

            $newEndExclusive = Carbon::parse($seriesEndStr)->addDay()->startOfDay();

            // --- Robuster Lösch-Block (ersetzen) ---
            $cutoff = $event->getRawOriginal('start_time') ?: (string) $event->start_time; // roher DB-Wert bevorzugt

            $query = Event::query()
                ->where('series_id', $series->id)
                ->where('id', '!=', $event->id)
                ->where('start_time', '>', $cutoff);

            $this->forceDeleteSeriesEventsWithCascade($query->get());

            [$nextStart, $nextEnd] = $this->generateNextOccurrence($eventStart->copy(), $eventEnd->copy(), $freq);

            while ($nextEnd < $newEndExclusive) {
                $this->createSeriesEvent($nextStart->copy(), $nextEnd->copy(), $request, $series, $event->project_id, $event);
                [$nextStart, $nextEnd] = $this->generateNextOccurrence($nextStart, $nextEnd, $freq);
            }

            return;
        }

        // FALL C (NEU): Serie existiert, Nutzer deaktiviert is_series => Zukunft löschen & Event entkoppeln
        if ($event->is_series && !$wantsSeries) {
            /** @var SeriesEvents|null $series */
            $series = SeriesEvents::find($event->series_id);
            if (!$series) {
                // Falls das Serienobjekt fehlt, einfach am Event deaktivieren
                $event->update(['is_series' => false, 'series_id' => null]);
                return;
            }

            $query = Event::query()
                ->where('series_id', $series->id)
                ->where('id', '!=', $event->id);


            $this->forceDeleteSeriesEventsWithCascade($query->get());

            // Aktuelles Event aus der Serie lösen
            $event->update(['is_series' => false, 'series_id' => null]);

            // Optionales Aufräumen: Serie nur löschen, wenn KEIN Event mehr darauf verweist.
            // withTrashed(), da auch soft-deleted Events die FK-Zeile halten und das Löschen sonst fehlschlägt.
            $stillReferenced = Event::withTrashed()->where('series_id', $series->id)->exists();
            if (!$stillReferenced) {
                $series->delete();
            }

            return;
        }
    }


    /**
     * Liefert nächste Instanz (Start/Ende) basierend auf Frequency-ID.
     * 1=daily(+1 Tag), 2=weekly(+1 Woche), 3=biweekly(+2 Wochen), 4=monthly(+1 Monat)
     */
    protected function generateNextOccurrence(Carbon $start, Carbon $end, int $frequency): array
    {
        $nextStart = $start->copy();
        $nextEnd   = $end->copy();

        switch ($frequency) {
            case 1:
                $nextStart->addDay();
                $nextEnd->addDay();
                break;
            case 2:
                $nextStart->addWeek();
                $nextEnd->addWeek();
                break;
            case 3:
                $nextStart->addWeeks(2);
                $nextEnd->addWeeks(2);
                break;
            case 4:
                $nextStart->addMonthNoOverflow();
                $nextEnd->addMonthNoOverflow();
                break;
            default:
                $nextStart->addWeek();
                $nextEnd->addWeek();
                break;
        }

        return [$nextStart, $nextEnd];
    }


    public function getCollisionCount(Request $request): int
    {
        // Ungültige Datums-Strings sollen 422 statt 500 liefern
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['start'])->setTimezone(config('app.timezone'));
        $end = Carbon::parse($validated['end'])->setTimezone(config('app.timezone'));

        return Event::query()
            ->startAndEndTimeOverlap($start, $end)
            ->where('room_id', $request->query('roomId'))
            ->where('id', '!=', $request->query('eventId'))
            ->count();
    }

    public function getTrashed(Request $request): Response|ResponseFactory
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('entitiesPerPage', 25);

        $trashedEvents = Event::onlyTrashed()
            ->with(['project', 'event_type', 'room'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('eventName', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhereHas('project', fn ($project) => $project->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($event) => [
                'id' => $event->id,
                'name' => $event->eventName,
                'project' => $event->project,
                'event_type' => $event->event_type,
                'start' => $event->start_time?->format('d.m.Y, H:i'),
                'end' => $event->end_time?->format('d.m.Y, H:i'),
                'room_name' => $event->room?->label,
            ]);

        return inertia('Trash/Events', [
            'trashed_events' => $trashedEvents,
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

        $this->craftInventoryItemEventService->deleteAllEventsFromInventory($event);

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
        //$eventBeforeDelete = $event->replicate();
        $this->authorize('delete', $event);
        // Broadcasts nach der Response (hängender Websocket-Server bremst sonst den Client)
        dispatch(static function () use ($event): void {
            broadcast(new RemoveEvent($event, $event->room_id));
            broadcast(new BulkEventChanged($event, 'deleted'));
        })->afterResponse();
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

        $this->craftInventoryItemEventService->deleteAllEventsFromInventory($event);
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
     * Delete all events in a series
     * @throws AuthorizationException
     */
    public function destroySeriesEvents(
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
        // Check if event is part of a series
        if (!$event->is_series || !$event->series_id) {
            return;
        }

        // Get all events in the series
        $seriesEvents = Event::where('series_id', $event->series_id)->get();

        // Delete each event in the series
        foreach ($seriesEvents as $seriesEvent) {
            // Authorize delete for each event
            $this->authorize('delete', $seriesEvent);

            // Delete the event
            $this->eventService->delete(
                $seriesEvent,
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

            // Check and delete from inventory if needed
            $this->craftInventoryItemEventService->deleteAllEventsFromInventory($seriesEvent);
        }
    }

    /**
     * Update all events in a series (room change and/or time shift)
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.TooHigh
    public function updateSeriesEvents(Event $event, Request $request): void
    {
        $this->authorize('update', $event);
        if (!$event->is_series || !$event->series_id) {
            return;
        }

        $seriesEvents = Event::where('series_id', $event->series_id)->get();

        foreach ($seriesEvents as $seriesEvent) {
            if ($request->get('newRoomId') !== null) {
                $seriesEvent->setAttribute('room_id', $request->integer('newRoomId'));
            }

            if ($request->integer('value') !== 0) {
                $endDate = Carbon::parse($seriesEvent->getAttribute('end_time'));
                $startDate = Carbon::parse($seriesEvent->getAttribute('start_time'));
                $shifts = $seriesEvent->shifts;
                $calculationType = $request->integer('calculationType');
                $value = $request->integer('value');
                $type = $request->integer('type');

                // Invalidate cache for all workers on all shifts before date changes
                foreach ($shifts as $shift) {
                    $this->workingHourCacheService->forgetForShift($shift);
                }

                if ($calculationType === 1) {
                    if ($type === 1) {
                        $seriesEvent->setAttribute('start_time', $startDate->addHours($value));
                        $seriesEvent->setAttribute('end_time', $endDate->addHours($value));
                    }
                    if ($type === 2) {
                        $seriesEvent->setAttribute('start_time', $startDate->addDays($value));
                        $seriesEvent->setAttribute('end_time', $endDate->addDays($value));
                        foreach ($shifts as $shift) {
                            $shift->setAttribute('start_date', Carbon::parse($shift->getAttribute('start_date'))->addDays($value));
                            $shift->setAttribute('end_date', Carbon::parse($shift->getAttribute('end_date'))->addDays($value));
                            $shift->save();
                        }
                    }
                    if ($type === 3) {
                        $seriesEvent->setAttribute('start_time', $startDate->addWeeks($value));
                        $seriesEvent->setAttribute('end_time', $endDate->addWeeks($value));
                        foreach ($shifts as $shift) {
                            $shift->setAttribute('start_date', Carbon::parse($shift->getAttribute('start_date'))->addWeeks($value));
                            $shift->setAttribute('end_date', Carbon::parse($shift->getAttribute('end_date'))->addWeeks($value));
                            $shift->save();
                        }
                    }
                    if ($type === 4) {
                        $seriesEvent->setAttribute('start_time', $startDate->addMonths($value));
                        $seriesEvent->setAttribute('end_time', $endDate->addMonths($value));
                        foreach ($shifts as $shift) {
                            $shift->setAttribute('start_date', Carbon::parse($shift->getAttribute('start_date'))->addMonths($value));
                            $shift->setAttribute('end_date', Carbon::parse($shift->getAttribute('end_date'))->addMonths($value));
                            $shift->save();
                        }
                    }
                    if ($type === 5) {
                        $seriesEvent->setAttribute('start_time', $startDate->addYears($value));
                        $seriesEvent->setAttribute('end_time', $endDate->addYears($value));
                        foreach ($shifts as $shift) {
                            $shift->setAttribute('start_date', Carbon::parse($shift->getAttribute('start_date'))->addYears($value));
                            $shift->setAttribute('end_date', Carbon::parse($shift->getAttribute('end_date'))->addYears($value));
                            $shift->save();
                        }
                    }
                }

                if ($calculationType === 2) {
                    if ($type === 1) {
                        $seriesEvent->setAttribute('start_time', $startDate->subHours($value));
                        $seriesEvent->setAttribute('end_time', $endDate->subHours($value));
                    }
                    if ($type === 2) {
                        $seriesEvent->setAttribute('start_time', $startDate->subDays($value));
                        $seriesEvent->setAttribute('end_time', $endDate->subDays($value));
                        foreach ($shifts as $shift) {
                            $shift->setAttribute('start_date', Carbon::parse($shift->getAttribute('start_date'))->subDays($value));
                            $shift->setAttribute('end_date', Carbon::parse($shift->getAttribute('end_date'))->subDays($value));
                            $shift->save();
                        }
                    }
                    if ($type === 3) {
                        $seriesEvent->setAttribute('start_time', $startDate->subWeeks($value));
                        $seriesEvent->setAttribute('end_time', $endDate->subWeeks($value));
                        foreach ($shifts as $shift) {
                            $shift->setAttribute('start_date', Carbon::parse($shift->getAttribute('start_date'))->subWeeks($value));
                            $shift->setAttribute('end_date', Carbon::parse($shift->getAttribute('end_date'))->subWeeks($value));
                            $shift->save();
                        }
                    }
                    if ($type === 4) {
                        $seriesEvent->setAttribute('start_time', $startDate->subMonths($value));
                        $seriesEvent->setAttribute('end_time', $endDate->subMonths($value));
                        foreach ($shifts as $shift) {
                            $shift->setAttribute('start_date', Carbon::parse($shift->getAttribute('start_date'))->subMonths($value));
                            $shift->setAttribute('end_date', Carbon::parse($shift->getAttribute('end_date'))->subMonths($value));
                            $shift->save();
                        }
                    }
                    if ($type === 5) {
                        $seriesEvent->setAttribute('start_time', $startDate->subYears($value));
                        $seriesEvent->setAttribute('end_time', $endDate->subYears($value));
                        foreach ($shifts as $shift) {
                            $shift->setAttribute('start_date', Carbon::parse($shift->getAttribute('start_date'))->subYears($value));
                            $shift->setAttribute('end_date', Carbon::parse($shift->getAttribute('end_date'))->subYears($value));
                            $shift->save();
                        }
                    }
                }
            }

            $seriesEvent->save();
            broadcast(new EventCreated($seriesEvent->fresh(), $seriesEvent->fresh()->room_id));
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(
        int $id,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService
    ): RedirectResponse {
        $event = Event::onlyTrashed()->findOrFail($id);

        $this->authorize('delete', $event);

        // Über den Service löschen, damit die mitgetrashten Schichten (inkl.
        // shift_workers), Timelines und Kommentare nicht als Leichen zurückbleiben
        // (der FK würde nur event_id auf NULL setzen).
        $this->eventService->forceDeleteAll(
            [$event],
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService
        );

        return Redirect::route('events.trashed');
    }

    public function forceDeleteAll(
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService
    ): RedirectResponse {
        $this->eventService->forceDeleteAll(
            Event::onlyTrashed()->get(),
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService
        );

        return Redirect::route('events.trashed');
    }

    public function restore(
        int $id,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService
    ): RedirectResponse {
        /** @var Event $event */
        $event = Event::onlyTrashed()->findOrFail($id);

        // Stale Projekt-Zeiger vor dem Restore bereinigen: EventService::restore
        // greift bei gesetzter project_id auf $event->project->id zu und würde bei
        // einem zwischenzeitlich gelöschten Projekt crashen.
        if ($event->project_id && !$event->project()->exists()) {
            $event->project_id = null;
            $event->saveQuietly();
        }

        // Über den Service wiederherstellen, damit auch die mitgetrashten
        // Schichten (inkl. shift_workers), Timelines, Kommentare und SubEvents
        // zurückkommen — nicht nur das Event selbst.
        $this->eventService->restore(
            $event,
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService,
            $changeService,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService
        );

        return Redirect::route('events.trashed');
    }

    /**
     * Hard-Delete von Serien-Events (Serie kürzen / is_series deaktivieren) MIT Kaskade.
     *
     * Vorher lief hier ein Query-Builder-forceDelete: Der shifts-FK ist ON DELETE SET NULL,
     * d.h. die Schichten der gelöschten Events lebten als Geister-Schichten weiter —
     * inklusive eingeplanter Mitarbeiter. timelines/sub_events haben gar keinen FK und
     * blieben als verwaiste Zeilen zurück.
     */
    private function forceDeleteSeriesEventsWithCascade(\Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $eventsToDelete): void
    {
        if ($eventsToDelete->isEmpty()) {
            return;
        }

        $shiftService = app(ShiftService::class);
        $timelineService = app(TimelineService::class);
        $subEventService = app(SubEventService::class);
        $notificationService = app(NotificationService::class);

        foreach ($eventsToDelete as $eventToDelete) {
            broadcast(new RemoveEvent($eventToDelete, $eventToDelete->room_id));

            $shiftService->forceDeleteShifts($eventToDelete->shifts);
            $timelineService->forceDeleteTimelines($eventToDelete->timelines);
            $subEventService->forceDeleteSubEvents($eventToDelete->subEvents);
            $notificationService->deleteUpsertRoomRequestNotificationByEventId($eventToDelete->id);

            $eventToDelete->forceDelete();
        }
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

    private function checkAdmissionTimeChanges($eventId, ?string $oldAdmissionTime, ?string $newAdmissionTime): void
    {
        // TIME-Spalte liefert "HH:mm:ss", Request "HH:mm" — auf HH:mm normalisieren
        $normalize = static fn (?string $time): ?string => $time ? substr($time, 0, 5) : null;

        if ($normalize($oldAdmissionTime) !== $normalize($newAdmissionTime)) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($eventId)
                    ->setTranslationKey('Admission time changed')
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
            $event = $eventService->findEventById($eventId);

            if ($event === null) {
                continue;
            }
            $this->authorize('delete', $event);

            $eventService->delete(
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

            if ($event === null) {
                continue;
            }
            $this->authorize('update', $event);

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
            broadcast(new EventCreated($event->fresh(), $event->fresh()->room_id));
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
        // Aus dem Planungskalender heraus erzeugte Duplikate sind immer geplante Termine
        $forcePlanning = $request->boolean('isPlanning');

        foreach ($eventIds as $eventId) {
            $originalEvent = $this->eventService->findEventById($eventId);

            if ($originalEvent === null) {
                continue;
            }
            $this->authorize('update', $originalEvent);

            $duplicatedEvent = $originalEvent->replicate();
            $duplicatedEvent->series_id = null;
            $duplicatedEvent->is_series = false;
            if ($forcePlanning) {
                $duplicatedEvent->is_planning = true;
            }
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
            broadcast(new EventCreated($event->fresh(), $event->fresh()->room_id));
        }
    }


    /**
     * Bulk-Endpunkte legen Termine direkt an (ohne Anfrage-Workflow) — deshalb reicht die
     * EventPolicy::create (die auch reine Anfrage-Berechtigte durchlässt) hier nicht aus.
     * Spiegelt das Frontend-Gating in BulkBody.vue (hasCreateEventsPermission);
     * Admins passieren über Gate::before.
     */
    private function authorizeBulkEventCreation(bool $isPlanning, Room $room): void
    {
        $user = $this->authManager->user();

        if ($isPlanning) {
            abort_unless($user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value), 403);

            return;
        }

        $mayCreateRegularEvent = $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value)
            || $room->everyone_can_book
            || $room->admins()->where('user_id', $user->id)->exists();

        abort_unless($mayCreateRegularEvent, 403);
    }

    public function bulkProjectEventStore(
        EventBulkCreateRequest $request,
        Project $project
    ): JsonResponse {
        $this->authorize('view', $project);

        $events = $request->input('events', []);

        // Erst ALLE Zeilen autorisieren, dann anlegen — sonst stehen bei einem 403
        // mitten in der Liste die ersten Termine bereits in der DB (Teil-Erstellung).
        foreach ($events as $event) {
            $room = Room::query()->findOrFail($event['room']['id']);
            $this->authorizeBulkEventCreation((bool) ($event['is_planning'] ?? false), $room);
        }

        $storedEvents = DB::transaction(function () use ($events, $project): array {
            $stored = [];

            foreach ($events as $event) {
                $stored[] = $this->eventService->createBulkEvent(
                    $event,
                    $project,
                    $this->authManager->id()
                );
            }

            return $stored;
        });

        $createdEventPayloads = [];
        $freshEvents = [];
        foreach ($storedEvents as $storedEvent) {
            $freshEvent = $storedEvent->fresh();
            $freshEvents[] = $freshEvent;
            $createdEventPayloads[] = \Artwork\Modules\Event\Events\BulkEventChanged::eventPayload($freshEvent);
        }

        // Broadcasts (ShouldBroadcastNow) erst NACH der Response senden — ein hängender
        // Websocket-Server darf die Antwort an den auslösenden Client nicht verzögern.
        dispatch(static function () use ($freshEvents): void {
            foreach ($freshEvents as $freshEvent) {
                broadcast(new \Artwork\Modules\Event\Events\BulkEventChanged($freshEvent, 'created'));
                broadcast(new EventCreated($freshEvent, $freshEvent->room_id));
            }
        })->afterResponse();

        // Erstellte Events zurückgeben: der auslösende Client aktualisiert seine Liste
        // aus der Response und ist damit nicht auf den eigenen Broadcast angewiesen.
        return new JsonResponse(['events' => $createdEventPayloads]);
    }

    public function updateSingleBulkEvent(
        Request $request,
        Event $event
    ): void {
        $this->authorize('update', $event);

        $data =  $request->collect('data');
        $this->eventService->updateBulkEvent(
            $data,
            $event
        );

        $freshEvent = $event->fresh();
        // Broadcasts nach der Response — hängender Websocket-Server darf den Patch nicht bremsen
        dispatch(static function () use ($freshEvent): void {
            broadcast(new \Artwork\Modules\Event\Events\BulkEventChanged($freshEvent, 'updated'));
            broadcast(new EventUpdated($freshEvent, $freshEvent->room_id));
        })->afterResponse();
    }

    public function createSingleBulkEvent(
        Request $request,
        Project $project
    ): JsonResponse {
        $this->authorize('view', $project);

        $request->validate([
            'event' => ['required', 'array'],
            'event.room.id' => ['required', 'integer', 'exists:rooms,id'],
            'event.type.id' => ['required', 'integer', 'exists:event_types,id'],
            'event.is_planning' => ['sometimes', 'boolean'],
        ]);
        // WICHTIG: input('event') statt validated('event') — bei verschachtelten Regeln
        // entfernt Laravel alle nicht explizit validierten Keys aus dem Array. Dadurch
        // gingen day/end_day/name/Zeiten verloren und createBulkEvent fiel auf "heute" zurück.
        $data = $request->input('event');
        $room = Room::query()->findOrFail($data['room']['id']);
        $this->authorizeBulkEventCreation((bool) ($data['is_planning'] ?? false), $room);

        $event = $this->eventService->createBulkEvent(
            $data,
            $project,
            $this->authManager->id()
        );
        $freshEvent = $event->fresh();
        // Broadcasts nach der Response — hängender Websocket-Server darf den Create nicht bremsen
        dispatch(static function () use ($freshEvent): void {
            broadcast(new \Artwork\Modules\Event\Events\BulkEventChanged($freshEvent, 'created'));
            broadcast(new EventCreated($freshEvent, $freshEvent->room_id));
        })->afterResponse();

        // Erstelltes Event zurückgeben: der auslösende Client aktualisiert seine Liste
        // aus der Response und ist damit nicht auf den eigenen Broadcast angewiesen.
        return new JsonResponse([
            'event' => \Artwork\Modules\Event\Events\BulkEventChanged::eventPayload($freshEvent),
        ]);
    }

    public function updateDescription(Request $request, Event $event): void
    {
        $this->authorize('update', $event);

        $event->update($request->only(['description']));

        $freshEvent = $event->fresh();
        dispatch(static function () use ($freshEvent): void {
            broadcast(new EventCreated($freshEvent, $freshEvent->room_id));
        })->afterResponse();
    }


    public function bulkMultiEditEvent(Request $request): void
    {
        $eventIds = $request->collect('eventIds');

        foreach (Event::whereIn('id', $eventIds)->get() as $eventToAuthorize) {
            $this->authorize('update', $eventToAuthorize);
        }

        $this->eventService->bulkMultiEditEvent(
            $eventIds,
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

        // Broadcasts nach der Response (hängender Websocket-Server bremst sonst den Client)
        $events = Event::whereIn('id', $eventIds)->get();
        dispatch(static function () use ($events): void {
            foreach ($events as $event) {
                broadcast(new EventCreated($event, $event->room_id));
            }
        })->afterResponse();
    }

    public function bulkDeleteEvent(Request $request): void
    {
        $eventIds = $request->collect('eventIds');

        // Fetch events before deletion to broadcast them
        $events = Event::whereIn('id', $eventIds)->get();

        foreach ($events as $eventToAuthorize) {
            $this->authorize('delete', $eventToAuthorize);
        }

        $this->eventService->bulkDeleteEvent($eventIds);

        // Broadcasts nach der Response (hängender Websocket-Server bremst sonst den Client)
        dispatch(static function () use ($events): void {
            foreach ($events as $event) {
                if ($event->room_id !== null) {
                    broadcast(new RemoveEvent($event, $event->room_id));
                }
                broadcast(new BulkEventChanged($event, 'deleted'));
            }
        })->afterResponse();
    }

    /**
     * Multi-Edit im Kalender: legt EINEN Termin (gleiche Daten) in mehreren
     * Tag×Raum-Zellen an — je Zelle ein eigenständiges Event.
     */
    public function createEventsInCells(Request $request): void
    {
        $validated = $request->validate([
            'cells' => ['required', 'array', 'min:1', 'max:' . self::MAX_MULTI_CELL_TARGETS],
            'cells.*.day' => ['required', 'date_format:Y-m-d'],
            'cells.*.room_id' => ['required', 'exists:rooms,id'],
            'event_type_id' => ['required', 'exists:event_types,id'],
            'event_status_id' => ['nullable', 'exists:event_statuses,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'is_planning' => ['nullable', 'boolean'],
        ]);

        // Gleiche Härtung wie in storeEvent: Termine dürfen nur in Projekte gelegt
        // werden, die der User auch sehen darf (IDOR-Schutz).
        if (!empty($validated['project_id'])) {
            $this->authorize('view', Project::query()->findOrFail($validated['project_id']));
        }

        $cells = $this->uniqueMultiCells($validated['cells']);
        $isPlanning = (bool) ($validated['is_planning'] ?? false);
        $rooms = Room::query()
            ->whereIn('id', collect($cells)->pluck('room_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($rooms as $room) {
            $this->authorizeBulkEventCreation($isPlanning, $room);
        }

        $eventStatusId = null;
        if (app(EventSettings::class)->enable_status) {
            $eventStatusId = $validated['event_status_id']
                ?? EventStatus::where('default', true)->first()?->id;
        }

        $createdEvents = DB::transaction(function () use ($cells, $validated, $eventStatusId, $isPlanning): array {
            $events = [];

            foreach ($cells as $cell) {
                [$startTime, $endTime, $allDay] = $this->eventService->processEventTimes(
                    Carbon::parse($cell['day']),
                    $validated['start_time'] ?? null,
                    $validated['end_time'] ?? null
                );

                $events[] = Event::create([
                    'name' => $validated['name'] ?? '',
                    'eventName' => $validated['name'] ?? '',
                    'description' => $validated['description'] ?? null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'allDay' => $allDay,
                    'event_type_id' => $validated['event_type_id'],
                    'event_status_id' => $eventStatusId,
                    'project_id' => $validated['project_id'] ?? null,
                    'room_id' => $cell['room_id'],
                    'user_id' => $this->authManager->id(),
                    'is_planning' => $isPlanning,
                ]);
            }

            return $events;
        }, attempts: 3);

        $this->broadcastCreatedEventsAfterResponse($createdEvents);
    }

    /**
     * Multi-Edit im Kalender: dupliziert die gewählten Termine in jede gewählte
     * Tag×Raum-Zelle (M×N Kopien). Uhrzeiten und Dauer bleiben erhalten,
     * Datum und Raum kommen aus der Zelle.
     */
    public function duplicateEventsToCells(Request $request): void
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:' . self::MAX_MULTI_CELL_SOURCE_EVENTS],
            'events.*' => ['distinct', 'exists:events,id'],
            'cells' => ['required', 'array', 'min:1', 'max:' . self::MAX_MULTI_CELL_TARGETS],
            'cells.*.day' => ['required', 'date_format:Y-m-d'],
            'cells.*.room_id' => ['required', 'exists:rooms,id'],
            'is_planning' => ['nullable', 'boolean'],
        ]);

        $cells = $this->uniqueMultiCells($validated['cells']);
        $duplicateCount = count($validated['events']) * count($cells);
        if ($duplicateCount > self::MAX_MULTI_CELL_DUPLICATES) {
            throw ValidationException::withMessages([
                'cells' => __('The selected events and cells would create too many duplicates.'),
            ]);
        }

        $forcePlanning = (bool) ($validated['is_planning'] ?? false);
        $originals = Event::query()->with('shifts')->whereIn('id', $validated['events'])->get();
        $rooms = Room::query()
            ->whereIn('id', collect($cells)->pluck('room_id')->unique())
            ->get();

        foreach ($originals as $original) {
            $this->authorize('update', $original);

            $isPlanning = $forcePlanning || (bool) $original->is_planning;
            foreach ($rooms as $room) {
                $this->authorizeBulkEventCreation($isPlanning, $room);
            }
        }

        $duplicatedEvents = DB::transaction(function () use ($originals, $cells, $forcePlanning): array {
            $duplicates = [];

            foreach ($originals as $original) {
                $originalStart = Carbon::parse($original->getAttribute('start_time'));

                foreach ($cells as $cell) {
                    $newStart = Carbon::parse($cell['day'])
                        ->setTimeFromTimeString($originalStart->toTimeString());
                    $dayDelta = $originalStart->copy()->startOfDay()
                        ->diffInDays($newStart->copy()->startOfDay(), false);

                    $duplicated = $original->replicate();
                    $duplicated->setAttribute('series_id', null);
                    $duplicated->setAttribute('is_series', false);
                    if ($forcePlanning) {
                        $duplicated->setAttribute('is_planning', true);
                    }
                    $duplicated->setAttribute('room_id', $cell['room_id']);
                    $duplicated->setAttribute('start_time', $newStart);
                    $duplicated->setAttribute(
                        'end_time',
                        Carbon::parse($original->getAttribute('end_time'))->addDays($dayDelta)
                    );
                    $duplicated->save();

                    foreach ($original->shifts as $shift) {
                        $duplicatedShift = $shift->replicate();
                        $duplicatedShift->setAttribute('event_id', $duplicated->id);
                        $duplicatedShift->setAttribute(
                            'start_date',
                            Carbon::parse($shift->getAttribute('start_date'))->addDays($dayDelta)
                        );
                        $duplicatedShift->setAttribute(
                            'end_date',
                            Carbon::parse($shift->getAttribute('end_date'))->addDays($dayDelta)
                        );
                        $duplicatedShift->save();
                    }

                    $duplicates[] = $duplicated;
                }
            }

            return $duplicates;
        }, attempts: 3);

        $this->broadcastCreatedEventsAfterResponse($duplicatedEvents);
    }

    /**
     * Multi-Edit im Kalender: verschiebt die gewählten Termine in genau EINE
     * Tag×Raum-Zelle. Uhrzeiten und Dauer bleiben erhalten (mehrtägige Termine
     * werden um das Tages-Delta verschoben), Datum und Raum kommen aus der Zelle;
     * Schichten wandern mit.
     */
    public function moveEventsToCell(Request $request): void
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:' . self::MAX_MULTI_CELL_SOURCE_EVENTS],
            'events.*' => ['distinct', 'exists:events,id'],
            'cell' => ['required', 'array'],
            'cell.day' => ['required', 'date_format:Y-m-d'],
            'cell.room_id' => ['required', 'exists:rooms,id'],
        ]);

        $room = Room::query()->findOrFail($validated['cell']['room_id']);
        $events = Event::query()->with('shifts')->whereIn('id', $validated['events'])->get();

        // Verschieben platziert den Termin direkt im Zielraum (ohne Anfrage-Workflow),
        // deshalb wie bei den Geschwister-Endpunkten erst ALLE Termine autorisieren.
        foreach ($events as $event) {
            $this->authorize('update', $event);
            $this->authorizeBulkEventCreation((bool) $event->getAttribute('is_planning'), $room);
        }

        $movedEvents = DB::transaction(function () use ($events, $validated, $room): array {
            $moved = [];

            foreach ($events as $event) {
                $start = Carbon::parse($event->getAttribute('start_time'));
                $newStart = Carbon::parse($validated['cell']['day'])
                    ->setTimeFromTimeString($start->toTimeString());
                $dayDelta = $start->copy()->startOfDay()
                    ->diffInDays($newStart->copy()->startOfDay(), false);

                $event->setAttribute('room_id', $room->getAttribute('id'));
                $event->setAttribute('start_time', $newStart);
                $event->setAttribute(
                    'end_time',
                    Carbon::parse($event->getAttribute('end_time'))->addDays($dayDelta)
                );
                $event->save();

                foreach ($event->shifts as $shift) {
                    $shift->setAttribute(
                        'start_date',
                        Carbon::parse($shift->getAttribute('start_date'))->addDays($dayDelta)
                    );
                    $shift->setAttribute(
                        'end_date',
                        Carbon::parse($shift->getAttribute('end_date'))->addDays($dayDelta)
                    );
                    $shift->save();
                }

                $moved[] = $event;
            }

            return $moved;
        }, attempts: 3);

        $this->broadcastCreatedEventsAfterResponse($movedEvents);
    }

    /**
     * Reverb failures must not turn a successfully committed bulk write into an HTTP 500.
     *
     * @param array<int, Event> $events
     */
    private function broadcastCreatedEventsAfterResponse(array $events): void
    {
        $eventIds = collect($events)->pluck('id')->all();

        dispatch(static function () use ($eventIds): void {
            Event::query()->whereIn('id', $eventIds)->each(
                static fn (Event $event) => broadcast(new EventCreated($event, $event->room_id))
            );
        })->afterResponse();
    }

    /**
     * @param array<int, array{day: string, room_id: int}> $cells
     * @return array<int, array{day: string, room_id: int}>
     */
    private function uniqueMultiCells(array $cells): array
    {
        $uniqueCells = collect($cells)
            ->unique(static fn (array $cell): string => $cell['day'] . ':' . $cell['room_id'])
            ->values();

        if ($uniqueCells->count() !== count($cells)) {
            throw ValidationException::withMessages([
                'cells' => __('The selected cells must be unique.'),
            ]);
        }

        return $uniqueCells->all();
    }

    public function bulkAcceptEvents(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'eventIds' => ['required', 'array', 'min:1', 'max:100'],
            'eventIds.*' => ['integer', 'distinct', 'exists:events,id'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);
        $events = Event::query()->whereIn('id', $validated['eventIds'])->get();
        $request->merge(['adminComment' => $validated['comment'] ?? null]);

        // Die Auswahl darf gemischt sein (Anfragen + normale Termine, Räume mit
        // und ohne Admin-Recht): nicht beantwortbare Events werden übersprungen,
        // statt den gesamten Batch mit 403 abzubrechen. Verarbeitet wird nur,
        // wofür die Berechtigung tatsächlich vorliegt.
        $events = $events->filter(fn (Event $event) => $request->user()->can('answerRoomRequest', $event));
        abort_if($events->isEmpty(), 403);

        foreach ($events as $event) {
            try {
                $this->acceptEvent($request, $event);
            } catch (HttpException $e) {
                // 409 = Anfrage wurde parallel bereits beantwortet → überspringen,
                // statt die Bulk-Verarbeitung mitten in der Liste abzubrechen.
                if ($e->getStatusCode() !== SymfonyResponse::HTTP_CONFLICT) {
                    throw $e;
                }
            }
        }

        return redirect()->back();
    }

    public function bulkDeclineEvents(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'eventIds' => ['required', 'array', 'min:1', 'max:100'],
            'eventIds.*' => ['integer', 'distinct', 'exists:events,id'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);
        $events = Event::query()->whereIn('id', $validated['eventIds'])->get();

        // Gemischte Auswahl: nicht beantwortbare Events überspringen statt Batch-403
        // (siehe bulkAcceptEvents).
        $events = $events->filter(fn (Event $event) => $request->user()->can('answerRoomRequest', $event));
        abort_if($events->isEmpty(), 403);

        foreach ($events as $event) {
            try {
                $this->declineEvent($request, $event);
            } catch (HttpException $e) {
                // 409 = Anfrage wurde parallel bereits beantwortet → überspringen,
                // statt die Bulk-Verarbeitung mitten in der Liste abzubrechen.
                if ($e->getStatusCode() !== SymfonyResponse::HTTP_CONFLICT) {
                    throw $e;
                }
            }
        }

        return redirect()->back();
    }

    public function standardEventValues()
    {
        return Inertia::render('Settings/StandardEventValues', []);
    }

    public function saveStandardEventValues(Request $request): void
    {
        $this->generalSettingsService->updateEventTimeLengthMinutesFromRequest($request);
        $this->generalSettingsService->updateEventStartTimeFromRequest($request);
        $this->generalSettingsService->updateEventAllDayDefaultFromRequest($request);
    }


    public function convertToPlanning(Event $event): RedirectResponse
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (
            !$user->hasRole(RoleEnum::ARTWORK_ADMIN->value) &&
            !$user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value)
        ) {
            abort(403);
        }

        $wasPlanning = $event->is_planning;

        // Set the event as a planning event
        $event->update(['is_planning' => true]);

        // Offene Raumanfrage-Benachrichtigungen zurückziehen - der Termin ist für
        // Raumadmins ohne Planungskalender-Zugriff nicht mehr sichtbar
        $this->notificationService->deleteUnhandledRoomRequestNotificationsByEventId($event->id);

        if (!$wasPlanning) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Event::class)
                    ->setModelId($event->id)
                    ->setTranslationKey('Event converted to planning event')
            );
        }

        // Broadcast the event update
        $freshEvent = $event->fresh()->load(['event_type', 'project']);
        broadcast(new EventUpdated(
            $freshEvent,
            $freshEvent->room_id
        ));

        return Redirect::back();
    }

    /**
     * Get crafts that the current user is allowed to assign in shift planning.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCurrentUserCrafts(User $user): \Illuminate\Database\Eloquent\Collection
    {
        // If user is admin, return all crafts with qualifications
        if ($user->hasRole('artwork admin')) {
            return $this->craftService->getAll(['qualifications']);
        }

        // Get crafts that are assignable by all (not restricted)
        $assignableByAllCrafts = $this->craftService->getAssignableByAllCrafts();

        // Get crafts where user is explicitly allowed (restricted crafts via craft_users table)
        $userRestrictedCrafts = $user->crafts()->with(['qualifications'])->get();

        // Merge both collections and remove duplicates by craft id
        return $assignableByAllCrafts->merge($userRestrictedCrafts)->unique('id');
    }

    public function getShiftPlanWorkers(
        GetShiftPlanWorkersRequest $request,
        \Artwork\Modules\Shift\Services\ShiftPlanWorkflowStatusService $workflowStatusService
    ): JsonResponse {
        $validated = $request->validated();

        $startDate = IlluminateCarbon::parse($validated['start_date']);
        $endDate = IlluminateCarbon::parse($validated['end_date']);
        $craftIds = $validated['craft_ids'] ?? [];

        $user = $request->user();

        $usersForShifts = $this->workingHourService->getUsersWithPlannedWorkingHours(
            $startDate,
            $endDate,
            UserShiftPlanResource::class,
            true,
            $user,
            $craftIds
        );

        $freelancersForShifts = $this->freelancerService->getFreelancersWithPlannedWorkingHours(
            $startDate,
            $endDate,
            FreelancerShiftPlanResource::class,
            true,
            $user,
            $craftIds
        );

        $serviceProvidersForShifts = $this->serviceProviderService->getServiceProvidersWithPlannedWorkingHours(
            $startDate,
            $endDate,
            ServiceProviderShiftPlanResource::class,
            $user,
            $craftIds
        );

        // Projektzuordnungen (verbindlich + Wunsch) je Person/Tag — eine Batch-Query pro Worker-Typ
        $dayAssignmentService = app(\Artwork\Modules\Project\Services\ProjectDayAssignmentService::class);
        $usersForShifts = $dayAssignmentService->attachAssignmentsToWorkerEntries(
            $usersForShifts,
            'user',
            User::class,
            $startDate,
            $endDate
        );
        $freelancersForShifts = $dayAssignmentService->attachAssignmentsToWorkerEntries(
            $freelancersForShifts,
            'freelancer',
            Freelancer::class,
            $startDate,
            $endDate
        );
        $serviceProvidersForShifts = $dayAssignmentService->attachAssignmentsToWorkerEntries(
            $serviceProvidersForShifts,
            'service_provider',
            ServiceProvider::class,
            $startDate,
            $endDate
        );

        $workflowStatus = $workflowStatusService->computeForDateRange(
            $startDate,
            $endDate,
            craftIds: $craftIds
        );
        $attachWorkflowStatus = static function (array $entries, string $workerKey) use ($workflowStatus): array {
            foreach ($entries as &$entry) {
                $workerId = $entry[$workerKey]['id'] ?? null;
                $entry['weeklyWorkflowStatus'] = $workerId !== null
                    ? ($workflowStatus[$workerKey][$workerId] ?? [])
                    : [];
            }

            return $entries;
        };

        return new JsonResponse([
            'usersForShifts' => $attachWorkflowStatus($usersForShifts, 'user'),
            'freelancersForShifts' => $attachWorkflowStatus($freelancersForShifts, 'freelancer'),
            'serviceProvidersForShifts' => $attachWorkflowStatus($serviceProvidersForShifts, 'service_provider'),
        ]);
    }

    /**
     * Return crafts for shift plan (loaded asynchronously by frontend).
     */
    public function getShiftPlanCrafts(Request $request): JsonResponse
    {
        $eagerLoad = [
            'users:id,first_name,last_name,position,profile_photo_path',
            'freelancers:id,first_name,last_name,position,profile_image',
            'serviceProviders:id,provider_name,profile_image',
            'qualifications:id,name,icon,available',
        ];

        if (!$request->boolean('lightweight')) {
            $eagerLoad[] = 'managingUsers:id';
            $eagerLoad[] = 'managingFreelancers:id';
            $eagerLoad[] = 'managingServiceProviders:id';
        }

        $crafts = Craft::with($eagerLoad)->get();

        return new JsonResponse(['crafts' => $crafts]);
    }

    /**
     * Reload a single worker for the shift plan (lightweight alternative to getShiftPlanWorkers).
     */
    public function getShiftPlanWorkerSingle(
        Request $request,
        \Artwork\Modules\Freelancer\Repositories\FreelancerRepository $freelancerRepository
    ): JsonResponse {
        $validated = $request->validate([
            'worker_id' => 'required|integer',
            'worker_type' => 'required|string|in:user,freelancer,serviceProvider',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = IlluminateCarbon::parse($validated['start_date']);
        $endDate = IlluminateCarbon::parse($validated['end_date']);
        $workerId = (int) $validated['worker_id'];
        $workerType = $validated['worker_type'];

        $modelClass = match ($workerType) {
            'user' => User::class,
            'freelancer' => Freelancer::class,
            'serviceProvider' => ServiceProvider::class,
        };

        $workers = $this->workerService->getWorkersForShiftPlanByIds($modelClass, [$workerId], $startDate, $endDate);

        if ($workers->isEmpty()) {
            return new JsonResponse(['worker' => null, 'workerType' => $workerType]);
        }

        $workers = $this->workerShiftPlanService->loadWorkerRelations($workers, $startDate, $endDate);
        $qualificationsCache = $this->workerService->buildQualificationsCache($workers);

        $worker = $workers->first();

        $resourceClass = match ($workerType) {
            'user' => UserShiftPlanResource::class,
            'freelancer' => FreelancerShiftPlanResource::class,
            'serviceProvider' => ServiceProviderShiftPlanResource::class,
        };

        $resource = $resourceClass::make($worker);
        $additionalData = [];

        // Parität zum Bulk-Load: Stundenkonto/KW-Stunden anderer nur mit Berechtigung, eigene immer
        $viewer = $request->user();
        $showHours = $viewer->can(PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value)
            || ($workerType === 'user' && $workerId === $viewer->id);

        if ($workerType === 'user') {
            $additionalData['workTimeBalance'] = $showHours
                ? $this->workingHourService->convertMinutesInHours($worker->work_time_balance ?? 0)
                : null;
        }

        $additionalData['weeklyWorkingHours'] = $showHours
            ? $this->workingHourService->calculateWeeklyWorkingHours($worker, $startDate, $endDate)
            : [];

        $workerData = $this->workerShiftPlanService->buildWorkerData(
            $worker,
            $resource,
            $qualificationsCache,
            $startDate,
            $endDate,
            // Parität zum Bulk-Load: User UND Freelancer liefern vacations mit,
            // sonst verliert die Worker-Zeile beim Einzel-Reload ihre Abwesenheiten
            in_array($workerType, ['user', 'freelancer'], true),
            $additionalData
        );

        if ($workerType === 'user') {
            $workerData = $this->workerShiftPlanService->enrichUserWorkerData(
                $workerData, $workerId, $startDate, $endDate, $worker
            );
        }

        // Parität zum Bulk-Load (FreelancerService): sonst verschwinden registrierte
        // Verfügbarkeiten der Zeile nach einem Einzel-Reload
        if ($workerType === 'freelancer') {
            $workerData['availabilities'] = $freelancerRepository
                ->getAvailabilitiesBetweenDatesGroupedByFormattedDate($worker, $startDate, $endDate);
        }

        // Parität zum Bulk-Load: KW-Kachel-Färbung (angefragt/festgeschrieben/Achtung)
        $workflowStatus = app(\Artwork\Modules\Shift\Services\ShiftPlanWorkflowStatusService::class)
            ->computeForDateRange($startDate, $endDate, $modelClass, $workerId);
        $workerDataKey = match ($workerType) {
            'user' => 'user',
            'freelancer' => 'freelancer',
            'serviceProvider' => 'service_provider',
        };
        $workerData['weeklyWorkflowStatus'] = $workflowStatus[$workerDataKey][$workerId] ?? [];

        // Parität zum Bulk-Load: Projektzuordnungen je Tag
        $workerData['project_assignments'] = app(\Artwork\Modules\Project\Services\ProjectDayAssignmentService::class)
            ->getAssignmentsGroupedByDate($modelClass, [$workerId], $startDate, $endDate)
            ->get($workerId) ?? new \stdClass();

        return new JsonResponse([
            'worker' => $workerData,
            'workerType' => $workerType,
        ]);
    }

    public function getTimelines(Event $event): JsonResponse
    {
        $event->load('timelines');

        return response()->json([
            'event' => $event,
            'timelines' => $event->timelines
        ]);
    }
}
