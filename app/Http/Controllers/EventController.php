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
use Artwork\Modules\Calendar\DTO\EventWithoutRoomDTO;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;
use Artwork\Modules\Calendar\DTO\RoomDTO;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\CalendarShiftVisibility;
use Artwork\Modules\Calendar\Services\EventCalendarService;
use Artwork\Modules\Calendar\Services\EventPlanningCalendarService;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Calendar\Services\ShiftPlanService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftScopeService;
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
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Event\Services\EventCollisionService;
use Artwork\Modules\Event\Services\DirectBookingActivationService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\Event\Services\EventCommentService;
use Artwork\Modules\Event\Services\SeriesEventsService;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Services\EventPropertyService;
use Artwork\Modules\Notification\Services\NotificationDialogDataService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\GlobalNotification\Services\GlobalNotificationService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Services\ShiftDeletionService;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\Room\Services\RoomService;
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
use Artwork\Modules\Shift\Http\Requests\CommitShiftsRequest;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Artwork\Modules\Shift\Services\ShiftConfirmationEligibilityService;

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
        private readonly SeriesEventsService $seriesEventsService,
        private readonly EventSettingsService $eventSettingsService,
    ) {
    }


    public function redirectToCalendar(Event $event): \Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = $this->authManager->user();

        // get full calendar week of event
        $startOfWeek = Carbon::parse($event->start_time)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::parse($event->end_time)->endOfWeek(Carbon::SUNDAY);



        $this->userService->focusCalendarOnPeriod($user, $startOfWeek, $endOfWeek, [
            'event_type_ids' => null,
            'room_ids' => null,
            'area_ids' => null,
            'room_attribute_ids' => null,
            'room_category_ids' => null,
            'event_property_ids' => null,
            'craft_ids' => null,
            // Blendet sonst den Zieltermin aus (und alle Termine ohne Projekt)
            'project_state_ids' => null,
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

        $this->userService->focusCalendarOnPeriod($user, $startDate, $endDate);

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
            'eventsWithoutRoom' => function () use ($userCalendarSettings) {
                // Lookup EINMAL bauen — vorher lief die EventType-Query in der
                // map-Closure und damit einmal pro raumlosem Event (N+1).
                $eventTypes = EventType::select(['id', 'name', 'abbreviation', 'hex_code'])->get()->keyBy('id');

                return $this->eventsWithoutRoomForCalendar($userCalendarSettings, $eventTypes);
            },
            'areas'            => fn () => $this->areaService->getAll(),
            'eventTypes'       => fn () => EventType::select(
                ['id', 'name', 'abbreviation', 'hex_code']
            )->orderBy('name')->get(),
            'eventStatuses'    => fn () => EventStatus::orderBy('order')->get(),
            'event_properties' => fn () => EventProperty::all(),
            'first_project_tab_id' => fn () => $this->projectTabService->getDefaultOrFirstProjectTabId(),
            'first_project_calendar_tab_id' => fn () => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'first_project_shift_tab_id' => fn () => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),

            'projectNameUsedForProjectTimePeriod' => fn () => $this->projectService
                ->resolveTimePeriodProject($userCalendarSettings)?->name,
            'filterType' => $calendarFilterType,
            'isDailyView' => $isDailyView,
            'shiftQualifications' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? $this->shiftQualificationService->getAllOrderedByPosition()
                : [],
            'globalQualifications' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? $this->globalQualificationService->getAll()
                : [],
            'crafts' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? Craft::query()
                    ->select(['id', 'name', 'abbreviation', 'color', 'universally_applicable', 'position'])
                    ->without(['craftShiftPlaner'])
                    ->orderBy('position')
                    ->get()
                : [],
            'currentUserCrafts' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? $this->getCurrentUserCrafts($user)
                : [],
            'shiftTimePresets' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? $this->shiftTimePresetService->getAll()
                : [],
            'shiftGroups' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
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
        // Gleiche Schranke wie viewPlanningCalendar(): die Daten-API darf nicht mehr zeigen als die Seite.
        abort_if($isPlanning && !$user->can('viewPlanning', Event::class), 403);

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

    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed
    /**
     * Raumlose Termine für Kalender und Planungskalender mit allem, was
     * EventWithoutRoomDTO/ProjectDTO sonst je Termin lazy nachladen würden
     * (Planungskalender vorher: 326 Queries je Seitenaufruf).
     *
     * @param \Illuminate\Support\Collection<int, EventType> $eventTypes
     * @return \Illuminate\Support\Collection<int, EventWithoutRoomDTO>
     */
    private function eventsWithoutRoomForCalendar(
        UserCalendarSettings|UserDailyViewCalendarSettings|null $userCalendarSettings,
        \Illuminate\Support\Collection $eventTypes
    ): \Illuminate\Support\Collection {
        return Event::query()
            ->hasNoRoom()
            ->with([
                'project:id,name,state,artists,is_group,color,icon',
                'project.status:id,name,color',
                'project.managerUsers:id,first_name,last_name,position,email,profile_photo_path',
                'project.groups',
                'project.users:id',
                'project.categories',
                'creator:id,first_name,last_name,position,email,profile_photo_path',
                'eventStatus:id,name,color',
                'eventProperties',
                'subEvents' => fn ($query) => $query->without('creator'),
            ])
            // statt Exists-Query je Termin über den has_verification-Accessor
            ->withExists(['verifications as has_pending_verification' => fn ($q) => $q->where('status', 'pending')])
            ->get()
            ->map(fn (Event $event) => EventWithoutRoomDTO::formModel($event, $userCalendarSettings, $eventTypes));
    }

    public function viewPlanningCalendar(Request $request, ?Project $project = null): Response
    {
        /** @var User $user */
        $user = $this->authManager->user();
        // Bisher nur im Menü versteckt; Admins passieren via Gate::before.
        abort_unless(
            $user->canAny([
                PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value,
                PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value,
            ]),
            403
        );
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
            'eventsWithoutRoom' => $this->eventsWithoutRoomForCalendar($userCalendarSettings, $eventTypes),
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
            'projectNameUsedForProjectTimePeriod' => $this->projectService
                ->resolveTimePeriodProject($userCalendarSettings)?->name,
            'calendarWarningText' => $calendarWarningText,
            'months' => $months,
            'verifierForEventTypIds' => $user->verifiableEventTypes->pluck('id'),
            'filterType' => $planningFilterType,
            'isDailyView' => $isDailyView,
            'shiftQualifications' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? $this->shiftQualificationService->getAllOrderedByPosition()
                : [],
            'globalQualifications' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? $this->globalQualificationService->getAll()
                : [],
            'crafts' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? Craft::query()
                    ->select(['id', 'name', 'abbreviation', 'color', 'universally_applicable', 'position'])
                    ->without(['craftShiftPlaner'])
                    ->orderBy('position')
                    ->get()
                : [],
            'currentUserCrafts' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? $this->getCurrentUserCrafts($user)
                : [],
            'shiftTimePresets' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
                ? $this->shiftTimePresetService->getAll()
                : [],
            'shiftGroups' => fn () => CalendarShiftVisibility::isEnabled($user, $userCalendarSettings)
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
                    'presets' => function ($q): void {
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

    public function viewShiftPlan(Request $request, ?Project $project = null): Response
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $isDailyView = (bool) $user->getAttribute('shift_plan_daily_view');

        // Deep-Link aus Benachrichtigungen (ShiftNotificationLinkService::shiftPlan):
        // start_date/end_date in der URL öffnen die betroffene Woche — nur für diesen Request
        // (in-memory auf dem Filter-Modell), der gespeicherte Zeitraum bleibt unverändert.
        $periodFromQuery = $this->shiftPlanPeriodFromRequest($request, $isDailyView);

        if ($isDailyView) {
            $shiftFilterType = UserFilterTypes::SHIFT_DAILY_FILTER->value;
            $userCalendarFilter = $user->userFilters()->firstOrCreate(
                ['filter_type' => $shiftFilterType],
                ['start_date' => null, 'end_date' => null]
            );
            $userCalendarSettings = $user->getAttribute('shift_plan_daily_settings')
                ?? $user->shift_plan_daily_settings()->firstOrCreate();
        } else {
            $shiftFilterType = UserFilterTypes::SHIFT_FILTER->value;
            $userCalendarFilter = $user->userFilters()->firstOrCreate(
                ['filter_type' => $shiftFilterType],
                ['start_date' => null, 'end_date' => null]
            );
            $userCalendarSettings = $user->getAttribute('shift_plan_settings')
                ?? $user->shift_plan_settings()->firstOrCreate();
        }

        // Deep-Link-Zeitraum nur im Speicher setzen (kein save()): wirkt auf getCalendarDateRange()
        // und den Prop user_filters, ohne die Datenbank zu verändern.
        if ($periodFromQuery !== null) {
            $userCalendarFilter->setAttribute('start_date', $periodFromQuery[0]->format('Y-m-d'));
            $userCalendarFilter->setAttribute('end_date', $periodFromQuery[1]->format('Y-m-d'));
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
            if ($periodFromQuery === null) {
                $user->userFilters()->updateOrCreate([
                    'filter_type' => $shiftFilterType
                ], [
                    'end_date' => $endDate->format('Y-m-d')
                ]);
            }
        }

        if ($isDailyView && $startDate->diffInDays($endDate) > 7) {
            $endDate = $startDate->copy()->addDays(7);
            $calendarWarningText = __('calendar.daily_view_info');
            if ($periodFromQuery === null) {
                $user->userFilters()->updateOrCreate([
                    'filter_type' => $shiftFilterType
                ], [
                    'end_date' => $endDate->format('Y-m-d')
                ]);
            }
        }

        // only allow six months in shift plan view
        if ($startDate->diffInDays($endDate) > 183) {
            $endDate = $startDate->copy()->addMonths(6);
            $calendarWarningText = __('calendar.calendar_limit_six_months');
            if ($periodFromQuery === null) {
                $user->userFilters()->updateOrCreate([
                    'filter_type' => $shiftFilterType
                ], [
                    'end_date' => $endDate->format('Y-m-d')
                ]);
            }
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
            // Bewusst OHNE 'users'/'freelancers'/'serviceProviders': die Worker-Zeilen
            // des Schichtplans werden aus shifts.workers gebaut (dropWorkers ->
            // craftWorkersMap gruppiert nach assigned_craft_ids), nicht aus den
            // Craft-Relationen. Die vollen User-Zeilen machten hier 2,38 MB von
            // 2,45 MB Props aus. Von den Managing-Relationen wird nur die id
            // gelesen (buildManagingSets), qualifications braucht das
            // AddShiftModal zum Auflösen der Schichtplätze.
            'crafts' => Craft::query()
                ->select(['id', 'name', 'abbreviation', 'color', 'universally_applicable', 'position'])
                ->with([
                    // Das Frontend liest hier nur die id — die Namens-/Bildspalten
                    // müssen trotzdem mit, weil die $appends der Modelle beim
                    // Serialisieren darauf zugreifen (ServiceProvider::getNameAttribute
                    // wirft ohne provider_name einen TypeError).
                    'managingUsers:id,first_name,last_name,profile_photo_path,work_time_balance',
                    'managingFreelancers:id,first_name,last_name,profile_image',
                    'managingServiceProviders:id,provider_name,profile_image',
                    'qualifications:id,name,icon,available',
                ])
                ->without(['craftShiftPlaner'])
                ->orderBy('position')
                ->get(),
            // Gewerke, die die Person festschreiben/planen darf (CraftScopeService; null = Admin, alle).
            // Das Festschreibungs-Modal filtert damit seine Gewerksliste — sonst liefe „alle Gewerke
            // auswählen" bei Nicht-Admins in den 422 aus CommitShiftsRequest.
            'plannableCraftIds' => static fn (): ?array => app(CraftScopeService::class)->plannableCraftIdsFor($user),
            'eventTypes' => EventType::all(),
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'event_properties' => EventProperty::all(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'personalFilters' => $this->filterService->getPersonalFilter($user, $shiftFilterType),
            'filterOptions' => $this->filterService->getCalendarFilterDefinitions(),
            'dateValue' => $dateValue,
            'user_filters' => $userCalendarFilter,
            'shiftQualifications' => $this->shiftQualificationService->getAllOrderedByPosition(),
            'dayServices' => $this->dayServicesService->getAll(),
            'firstProjectShiftTabId' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),
            'projectNameUsedForProjectTimePeriod' => $this->projectService
                ->resolveTimePeriodProject($userCalendarSettings)?->name,
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
                    'presets' => function ($q): void {
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

    /**
     * Zeitraum aus den Query-Parametern start_date/end_date (Deep-Link) — NUR für diesen Request,
     * wird nicht in user_filters gespeichert (Härtung: URL-Parameter dürfen keinen persistenten
     * Zustand schreiben). Rückgabe null, wenn kein gültiger Zeitraum übergeben wurde.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function shiftPlanPeriodFromRequest(Request $request, bool $isDailyView): ?array
    {
        $rawStart = $request->query('start_date');
        $rawEnd = $request->query('end_date');
        if (!is_string($rawStart) || $rawStart === '' || !is_string($rawEnd) || $rawEnd === '') {
            return null;
        }

        try {
            $start = Carbon::parse($rawStart)->startOfDay();
            $end = Carbon::parse($rawEnd)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }
        // Tagesansicht zeigt maximal sieben Tage (siehe Begrenzung unten)
        if ($isDailyView && $start->diffInDays($end) > 7) {
            $end = $start->copy()->addDays(7);
        }

        return [$start, $end];
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
            // ACHTUNG: users/freelancers/serviceProviders müssen hier bleiben.
            // Die Listenansicht reicht diese crafts an SingleShiftInDailyShiftView
            // weiter, dessen getAssignablePeople() daraus die Auswahlliste zum
            // Zuweisen von Personen auf Schichtplätze baut. Ohne die Relationen
            // bleibt dieses Dropdown leer. Schlanke Personen-Serialisierung: CraftService.
            'crafts' => $this->craftService->getAllWithAssignableWorkers(),
            'eventTypes' => EventType::select(['id', 'name', 'abbreviation', 'hex_code'])->get(),
            'filterOptions' => $this->filterService->getCalendarFilterDefinitions(),
            'personalFilters' => $this->filterService->getPersonalFilter($user, $shiftFilterType),
            'shiftQualifications' => $this->shiftQualificationService->getAllOrderedByPosition(),
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


    //@todo: fix phpcs error - fix complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function showDashboardPage(
        GlobalNotificationService $globalNotificationService,
        CarbonService $carbonService,
        EventPropertyService $eventPropertyService
    ): Response {
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
            // alles, was TaskDashboardResource je Aufgabe lesen würde
            ->with(['checklist:id,name,project_id', 'checklist.project:id,name', 'task_users', 'user_who_done'])
            ->get();

        $user = Auth::user();

        $now = $carbonService->getNow();
        $today = $now->format('Y-m-d');

        // Projektteam-Flag fürs Frontend-Gating des Projektlinks (SingleUserEventShift):
        // project.users mitzuschicken wäre zu schwer, ein Exists-Flag reicht.
        $withTeamFlag = static function ($query) use ($user): void {
            $query->withExists([
                'users as auth_user_in_team' => static function (Builder $builder) use ($user): void {
                    $builder->whereKey($user->id);
                },
            ]);
        };

        $shiftsOfDay = $user
            ->shifts()
            ->whereDate(
                'shifts.start_date',
                $today
            )->with([
                'event',
                'event.project' => $withTeamFlag,
                'event.room',
                'event.event_type',
                'room',
                'project' => $withTeamFlag,
                'craft',
                'users',
                'freelancer',
                'serviceProvider',
            ])->get();

        // Vereinheitlichte workers-Liste wie im Einsatzplan (EventService::getDaysWith…):
        // SingleUserEventShift liest shift.workers (inkl. type-Tag und Pivot) für
        // Kolleg*innen, individuelle Zeiten und die Zu-/Absage-Buttons.
        $confirmationEligibility = app(ShiftConfirmationEligibilityService::class);
        $shiftsOfDay->each(static function ($shift) use ($confirmationEligibility): void {
            $tag = static fn ($workers, string $type) => ($workers ?? collect())
                ->map(static function ($worker) use ($type, $confirmationEligibility) {
                    $worker->setAttribute('type', $type);
                    $worker->setAttribute('confirmation_eligible', $confirmationEligibility->isEligible($worker));
                    return $worker;
                });

            $shift->setAttribute(
                'workers',
                $tag($shift->users, 'user')
                    ->concat($tag($shift->freelancer, 'freelancer'))
                    ->concat($tag($shift->serviceProvider, 'service_provider'))
                    ->values()
                    ->all()
            );
            $shift->makeHidden(['users', 'freelancer', 'serviceProvider']);
        });

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

        // Dialoge der Benachrichtigungen – dieselbe Logik wie im Benachrichtigungscenter (vorher eigene
        // Kopie: Termin in {data: …} verpackt → „Bearbeiten & annehmen“ legte einen NEUEN Termin an,
        // „Belegung absagen“ und der Abwesenheitsverlauf fehlten ganz)
        $dialogData = app(NotificationDialogDataService::class)->forRequest(request(), $user);

        return inertia('Dashboard', [
            'tasks' => TaskDashboardResource::collection($tasks)->resolve(),
            // Pivot-Spalte ist DATE — Vergleich mit vollem Timestamp ($now) würde nur um
            // Mitternacht matchen und die Tagesdienste sonst immer leer lassen.
            'users_day_services_of_day' => $user->dayServices()->wherePivot('date', $today)->get(),
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
            'event' => $dialogData['event'],
            'wantedSplit' => $dialogData['wantedSplit'],
            'roomCollisions' => [],
            'eventTypes' => EventTypeResource::collection(EventType::query()->with('verifiers')->get())->resolve(),
            'rooms' => Room::select(['id', 'name', 'area_id', 'order'])->get(),
            'projects' => Project::select(['id', 'name'])->get(),
            'historyObjects' => $dialogData['historyObjects'],
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

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function storeEvent(EventStoreRequest $request): CalendarEventResource | RedirectResponse
    {
        $this->authorize('create', Event::class);

        // Bewusst KEINE Projekt-Prüfung (Entscheidung 06.10.2026): wer Termine anlegen darf, darf jedes
        // Projekt zuordnen bzw. im Termin-Dialog ein neues anlegen – die Projektsuche im Dialog bietet
        // alle Projekte an. Die frühere Prüfung lief über filled('projectId'), das bei diesem Request
        // nie anschlug (data() ist überschrieben), war also nie aktiv.

        // Server-side enforcement: verify the user can actually book or request for this room
        /** @var User $user */
        $user = $this->authManager->user();
        $roomId = $request->get('roomId');
        $isOption = $this->resolveRoomBookingOption(
            $user,
            $roomId ? (int) $roomId : null,
            $request->booleanValue('isPlanning'),
            $request->booleanValue('isOption')
        );
        $request->merge(['isOption' => $isOption]);

        /** @var Event $firstEvent */
        $firstEvent = Event::create($request->data());
        $firstEvent->eventProperties()->sync($request->input('event_properties', []));
        $this->adjoiningRoomsCheck($request, $firstEvent);
        if ($request->get('projectName')) {
            $this->associateProject($request, $firstEvent, $this->budgetService);
        }

        /** @var Project $projectFirstEvent */
        $projectFirstEvent = $firstEvent->project;

        if ($request->booleanValue('is_series')) {
            // Ausrollen der Serie zentral im SeriesEventsService (Turnus, Wochentage, Ende/Anzahl)
            $this->seriesEventsService->createSeriesForEvent(
                $firstEvent,
                $this->seriesDefinitionInput($request),
                $request->input('event_properties', [])
            );
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

        SafeBroadcast::send(new OccupancyUpdated(), toOthers: true);

        if ($request->booleanValue('showProjectPeriodInCalendar')) {
            return $this->redirector->back();
        }

        SafeBroadcast::send(new EventCreated(
            $firstEvent->load(['event_type', 'project']),
            $firstEvent->room_id
        ));

        return new CalendarEventResource($firstEvent);
    }

    /**
     * Raumrechte beim Anlegen von Terminen (Termin-Dialog und Ausrollen weiterer Serientermine):
     * entscheidet, ob der Termin fest gebucht werden darf oder zur Raumanfrage wird.
     * Ein veralteter oder manipulierter Client darf aus reinem Anfragerecht nie eine Direktbuchung machen.
     *
     * @return bool effektives isOption (true = Raumanfrage)
     * @throws ValidationException Raum fehlt, obwohl die Person nur anfragen darf
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function resolveRoomBookingOption(User $user, ?int $roomId, bool $isPlanning, bool $isOption): bool
    {
        // "Termine immer direkt buchbar": es gibt keine Raumanfragen – jede Person, die anlegen darf
        // (EventPolicy::create), bucht direkt; ein vom Client gesendetes isOption wird verworfen.
        $alwaysDirectBooking = $this->eventSettingsService->alwaysDirectBooking();
        if ($alwaysDirectBooking) {
            $isOption = false;
        }

        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return $isOption;
        }

        if (!$roomId) {
            // Kalender und Planungskalender sind getrennt berechtigt: geplante Termine direkt (ohne Raum)
            // anlegen darf nur "Im Planungskalender fest planen", reguläre nur "Termine fest planen".
            $canCreateWithoutRoom = $isPlanning
                ? $user->can(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value)
                : $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);

            if (!$canCreateWithoutRoom) {
                if ($user->can(PermissionEnum::EVENT_REQUEST->value)) {
                    throw ValidationException::withMessages([
                        'roomId' => $alwaysDirectBooking
                            ? __('A room is required for this event.')
                            : __('A room is required for a room request.'),
                    ]);
                }

                abort(403);
            }

            return $isOption;
        }

        $room = $alwaysDirectBooking ? null : Room::find($roomId);
        if ($room === null) {
            return $isOption;
        }

        ['canBookDirectly' => $canBookDirectly, 'canRequest' => $canRequest] =
            $this->roomBookingRights($user, $room, $isPlanning);

        if (!$isOption && !$canBookDirectly) {
            if (!$canRequest) {
                abort(403);
            }

            $isOption = true;
        }

        // Request booking (isOption=true): must have global request permission or room-specific can_request
        if ($isOption && !$canRequest) {
            abort(403);
        }

        return $isOption;
    }

    /**
     * Raumrechte einer Person in einem Raum (ohne "Termine immer direkt buchbar" – das prüfen die Aufrufer).
     *
     * @return array{canBookDirectly: bool, canRequest: bool}
     */
    private function roomBookingRights(User $user, Room $room, bool $isPlanning): array
    {
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return ['canBookDirectly' => true, 'canRequest' => true];
        }

        $isRoomAdmin = $room->admins()->where('user_id', $user->id)->exists();
        $hasGlobalCreate = $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $canPlanFixed = $isPlanning && $user->can(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value);
        // Direktbuchung: reguläre Termine über "Termine fest planen", geplante Termine NUR über
        // "Im Planungskalender fest planen" (getrennte Berechtigung, keine Implikation).
        $canBookDirectly = ($isPlanning ? $canPlanFixed : $hasGlobalCreate)
            || $isRoomAdmin
            || $room->everyone_can_book;
        $canRequest = $hasGlobalCreate
            || $isRoomAdmin
            || $room->everyone_can_book
            || $user->can(PermissionEnum::EVENT_REQUEST->value)
            || $room->requestableBy()->where('user_id', $user->id)->exists();

        return ['canBookDirectly' => $canBookDirectly, 'canRequest' => $canRequest];
    }

    public function commitShifts(CommitShiftsRequest $request, GeneralSettings $generalSettings): void
    {
        // Gleicher Guard wie changeCommitShifts(): bei aktivem Freigabe-Workflow läuft die
        // Festschreibung ausschließlich über Anfragen (ShiftPlanRequest) — auch für die KW-Sammelaktion.
        if ($generalSettings->shift_commit_workflow_enabled) {
            abort(422, __('While the approval workflow is active, shifts can only be committed via a request.'));
        }

        $weekNumber = $request->weekNumber();
        $year = $request->year();
        [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear($weekNumber, $year);

        // Mehrfachauswahl im Modal: craft_ids (Array) ODER einzelnes craft_id (Altbestand);
        // Validierung + Gewerks-Scoping (nur planbare Gewerke) im CommitShiftsRequest.
        $craftIds = $request->craftIds();
        foreach ($craftIds as $craftId) {
            $this->shiftService->commitShiftsByDate($start, $end, $craftId, $weekNumber, $year);
        }

        // Rueckmeldung fuer den globalen Flash-Toast (Block 2): vorher schloss das Modal ohne jede Meldung.
        $request->session()->flash('success', trans_choice(
            'Duty roster committed for calendar week :week (:count craft).|Duty roster committed for calendar '
                . 'week :week (:count crafts).',
            count($craftIds),
            ['week' => $weekNumber, 'count' => count($craftIds)]
        ));
    }

    public function changeCommitShifts(Request $request, Shift $shift, GeneralSettings $generalSettings): void
    {
        $committed = $request->boolean('commit');

        // Bei aktivem Freigabe-Workflow läuft die Festschreibung ausschließlich über
        // Anfragen (ShiftPlanRequest) — das Aufheben bleibt direkt möglich.
        if ($committed && $generalSettings->shift_commit_workflow_enabled) {
            abort(422, __('While the approval workflow is active, shifts can only be committed via a request.'));
        }

        app(CraftScopeService::class)->assertCanPlanShifts(Auth::user(), [$shift]);

        $shift->update([
            'is_committed' => $committed,
            'committing_user_id' => $committed ? Auth::id() : null,
        ]);

        // is_committed ist nicht in logOnly — Einzel-Toggle explizit loggen.
        $this->shiftService->logSingleCommitActivity($shift, $committed);

        // Schloss-Symbol in offenen Dienstplan-Ansichten ohne Neuladen aktualisieren
        $roomId = $shift->event_id ? $shift->event?->room_id : $shift->room_id;
        if ($roomId !== null) {
            SafeBroadcast::send(new UpdateShiftInShiftPlan($shift, (int) $roomId));
        }
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
            // Termin ohne Projekt (bzw. Projekt erst danach angelegt): vorher 500 nach dem Speichern
            3 => [
                'type' => 'link',
                'title' => $project?->name ?? '',
                'href' => $project === null ? null : route(
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
            // Termin ohne Projekt (bzw. Projekt erst danach angelegt): vorher 500 nach dem Speichern
            3 => [
                'type' => 'link',
                'title' => $project?->name ?? '',
                'href' => $project === null ? null : route(
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

    private function associateProject($request, $event, BudgetService $budgetService): void
    {
        $project = Project::create(['name' => $request->get('projectName')]);
        $budgetService->generateBasicBudgetValues($project);
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
        Event $event
    ): void {
        $this->authorize('update', $event);

        $shouldAcceptRoomRequest = $event->occupancy_option &&
            $request->has('isOption') &&
            !$request->booleanValue('isOption');

        if ($shouldAcceptRoomRequest) {
            $this->authorize('answerRoomRequest', $event);
        }

        // Reichweite der Bearbeitung bei Serienterminen (single | following | all).
        // allSeriesEvents ist der Alt-Parameter (RoomRequestDialogComponent) und bedeutet „all“.
        $seriesScope = $this->seriesEventsService->normalizeScope(
            $request->input('seriesScope') ?? ($request->booleanValue('allSeriesEvents') ? 'all' : 'single')
        );

        // Ausrollen weiterer Serientermine (Einzeltermin -> Serie, Serie verlängern/Turnus ändern) legt neue
        // Termine an: dieselbe Raumrechte-Prüfung wie beim Anlegen (storeEvent), bezogen auf Raum und Art der
        // Vorlage, von der die neuen Termine kopiert werden. Vor jeder Änderung, damit ein 403 bzw.
        // Validierungsfehler den Termin nicht halb geändert zurücklässt. Nur wenn wirklich Termine entstehen –
        // der Dialog schickt bei „folgende/alle“ die Seriendefinition immer mit.
        $seriesRolloutAsRoomRequest = false;
        /** @var array<int, int> $deferredRoomRequestEventIds */
        $deferredRoomRequestEventIds = [];
        $rolloutTarget = $this->seriesRolloutTarget($request, $event, $seriesScope);
        if ($rolloutTarget !== null) {
            /** @var User $user */
            $user = $this->authManager->user();
            // isOption=false: gefragt ist nur, ob die Person im Raum fest buchen darf. Ist die Vorlage selbst
            // eine Anfrage, übernimmt cloneForOccurrence das ohnehin (vorher führte ein mitgeschicktes, gerade
            // angenommenes occupancy_option bei Admins zu neuen Anfragen).
            $seriesRolloutAsRoomRequest = $this->resolveRoomBookingOption(
                $user,
                $rolloutTarget['roomId'],
                $rolloutTarget['isPlanning'],
                false
            );
        }

        // Projektzuordnung bewusst ohne eigene Prüfung, siehe storeEvent()
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

        // „Keine Benachrichtigungen“ gilt auch für die Raumwechsel-Bestätigung
        if ($request->roomChange && !$request->noNotifications) {
            $room = Room::find($event->room_id);
            $project = Project::find($event->project_id);
            $projectManagers = $project?->managerUsers()->get() ?? collect();

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

        // Nur die tatsächlich geänderten Felder werden auf die Geschwister übertragen
        $seriesChangedFields = $event->getDirty();
        if (
            $event->is_series
            && $seriesScope === SeriesEventsService::SCOPE_SINGLE
            && $event->isDirty(['start_time', 'end_time', 'room_id'])
        ) {
            // Einzeln verschobener Serientermin: beim Neu-Ausrollen/Zeit-Delta der Serie schützen
            $event->is_series_exception = true;
        }

        // Nur synchronisieren, wenn der Dialog Eigenschaften mitschickt (sonst wurden sie gelöscht)
        $newEventPropertyIds = $request->has('event_properties')
            ? $request->input('event_properties', [])
            : $oldEventPropertyIds;
        $event->eventProperties()->sync($newEventPropertyIds);
        $this->eventService->save($event);
        // Die Policy hat die Raum-Relation schon mit dem ALTEN Raum geladen – nach einem Raumwechsel ginge die
        // Raumanfrage (notifyRoomAdmins liest $event->room) sonst an die Admins des alten Raums
        if ($event->wasChanged('room_id')) {
            $event->unsetRelation('room');
        }

        if ($shouldAcceptRoomRequest) {
            $this->acceptEvent($request, $event);
        }

        // Projekt ggf. anlegen & zuordnen (dein Original)
        if ($request->get('projectName')) {
            $project = Project::create(['name' => $request->get('projectName')]);
            $project->users()->save(Auth::user(), ['access_budget' => true]);
            $this->budgetService->generateBasicBudgetValues($project);
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
        $roomChangeBecameRequest = false;
        if (
            $event->room_id
            && (int) $oldEventRoom !== (int) $event->room_id
            && !$this->eventSettingsService->alwaysDirectBooking()
        ) {
            $newRoom = Room::find($event->room_id);
            if ($newRoom) {
                /** @var User $user */
                $user = $this->authManager->user();
                // Buchungsrecht wie beim Anlegen (roomBookingRights, inkl. Trennung Kalender/Planungskalender).
                // Anders als storeEvent bewusst KEIN 403 ohne Anfragerecht: ein Raumwechsel ohne Buchungsrecht
                // wurde schon immer zur Raumanfrage – das bleibt so.
                $canBookDirectly = $this->roomBookingRights(
                    $user,
                    $newRoom,
                    (bool) $event->is_planning
                )['canBookDirectly'];

                if (!$canBookDirectly) {
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
                    $roomChangeBecameRequest = true;
                }
            }
        }

        // Update existing room request notifications if event details changed while request is still pending
        if (!$roomChangeBecameRequest && !$event->is_planning && $event->occupancy_option && $event->room_id) {
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

        // try schon vor propagateToSiblings (läuft ohne Transaktion): scheitert es beim k-ten Geschwister, sind
        // die vorherigen bereits als Anfrage gespeichert und müssen im finally trotzdem gemeldet werden
        try {
            if ($event->is_series && $seriesScope !== SeriesEventsService::SCOPE_SINGLE) {
                $sortedOld = array_map('intval', $oldEventPropertyIds);
                $sortedNew = array_map('intval', $newEventPropertyIds);
                sort($sortedOld);
                sort($sortedNew);
                $propertiesChanged = $sortedOld !== $sortedNew;

                // Raumwechsel der Serie: dieselbe Raumrechte-Entscheidung wie für den bearbeiteten Termin –
                // wurde er zur Raumanfrage, werden es die mitverschobenen Geschwister auch (inkl. Benachrichtigung).
                $this->seriesEventsService->propagateToSiblings(
                    $event,
                    $seriesScope,
                    Carbon::parse($oldEventStartDate),
                    $seriesChangedFields,
                    (int) $diffStartMinutes,
                    (int) $diffEndMinutes,
                    $propertiesChanged ? $newEventPropertyIds : null,
                    $roomChangeBecameRequest,
                    $deferredRoomRequestEventIds
                );
            }

            $this->handleSeriesDefinitionOnUpdate(
                $request,
                $event,
                $seriesScope,
                $newEventPropertyIds,
                $seriesRolloutAsRoomRequest,
                $rolloutTarget
            );
        } finally {
            // Raumanfragen der mitverschobenen Geschwister erst jetzt: eine Definitionsänderung im selben Request
            // kann sie in den Papierkorb gelegt haben (sonst Anfrage + „gelöscht“ + neue Anfrage je Termin).
            // Im finally: die Geschwister sind schon als Anfrage gespeichert – auch wenn danach etwas scheitert,
            // müssen die Raumadmins davon erfahren (ein erneutes Speichern löst nichts mehr aus).
            $this->notifyRoomAdminsOfDeferredRequests($deferredRoomRequestEventIds);
        }

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

        // Projektzuordnungen (Re-Materialisierung/Auflösung bei Zeitraum-Änderung)
        // laufen zentral über den ProjectDayAssignmentEventObserver.

        // Live-Update über SafeBroadcast: ein Fehler nach dem vollständigen Speichern darf keine 500 liefern
        // (erneutes Speichern legte sonst z. B. Serien doppelt an)
        $freshEvent = $event->fresh();
        SafeBroadcast::send(new EventCreated($freshEvent, $freshEvent->room_id));
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
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', Auth::id())
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
        // Schon beantwortet (parallel durch andere Admins)? Dann 409 „bereits beantwortet“ –
        // die Policy hätte vorher mit 403 „nicht erlaubt“ abgelehnt
        abort_if(!$event->occupancy_option || $event->room_id === null, 409, 'This room request has already been answered.');
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

        SafeBroadcast::send(new EventUpdated($event->fresh(), $event->room_id));

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function declineEvent(Request $request, Event $event): RedirectResponse
    {
        abort_if($event->room_id === null, 409, 'This room request has already been answered.');
        $this->authorize('declineEvent', $event);

        $projectManagers = [];
        $roomId = $event->room_id;
        $room = $event->room()->first();
        $project = $event->project()->first();
        if (!empty($project)) {
            $projectManagers = $project->managerUsers()->get();
        }
        // Absichtlich ohne occupancy_option-Bedingung: Absagen gilt auch für
        // bereits bestätigte Belegungen. Der room_id-Guard hält die Antwort
        // atomar (parallele zweite Absage trifft 0 Zeilen → 409).
        $updated = Event::query()
            ->whereKey($event->id)
            ->whereNotNull('room_id')
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
            $this->notificationService->setRoomId($roomId); // room_id ist nach der Absage null
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

        $this->notificationService->setRoomId($roomId); // room_id ist nach der Absage null
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

        SafeBroadcast::send(new EventCreated(
            $event,
            $roomId
        ));

        return Redirect::back();
    }

    /**
     * @return array<string, mixed>
     */
    private function seriesDefinitionInput(Request $request): array
    {
        return [
            'frequency' => $request->input('seriesFrequency'),
            'end_date' => $request->input('seriesEndDate'),
            'weekdays' => $request->input('seriesWeekdays'),
            'occurrence_count' => $request->input('seriesOccurrenceCount'),
        ];
    }

    /**
     * Serie beim Bearbeiten anlegen oder ihre Definition (Turnus/Wochentage/Ende) abgleichen.
     * - Einzeltermin -> Serie: ausrollen ab dem nächsten Termin.
     * - Bestehende Serie: nur bei Reichweite „folgende“/„alle“ UND wenn das Frontend Turnus + Ende
     *   tatsächlich mitschickt (verhindert das frühere stille Umschreiben auf einen Default-Turnus).
     * - Serie abwählen läuft über events.series.detach mit Rückfrage – hier bewusst keine Aktion.
     *
     * @param array<int> $propertyIds
     * @param bool $asRoomRequest neu ausgerollte Termine als Raumanfrage anlegen (Raumrechte wie storeEvent)
     * @param array{roomId: ?int, isPlanning: bool, templateEventId: int}|null $rolloutTarget Ergebnis der
     *        Raumrechte-Prüfung vor dem Speichern (seriesRolloutTarget); null = keine Prüfung, weil zu dem
     *        Zeitpunkt keine neuen Termine zu erwarten waren
     */
    private function handleSeriesDefinitionOnUpdate(
        Request $request,
        Event $event,
        string $scope,
        array $propertyIds,
        bool $asRoomRequest = false,
        ?array $rolloutTarget = null
    ): void {
        if (!$this->requestsSeriesDefinition($request)) {
            return;
        }

        $definitionInput = $this->seriesDefinitionInput($request);

        if (!$event->is_series) {
            $this->seriesEventsService->createSeriesForEvent($event, $definitionInput, $propertyIds, $asRoomRequest);
            return;
        }

        if ($scope === SeriesEventsService::SCOPE_SINGLE) {
            return;
        }

        /** @var SeriesEvents|null $series */
        $series = SeriesEvents::query()->find($event->series_id);
        if (!$series) {
            return;
        }

        // Ungeprüft (vor dem Speichern entstand laut Plan nichts, die Zeitverschiebung hat das geändert):
        // neue Termine sicherheitshalber nur als Anfrage – außer es gibt keine Raumanfragen bzw. für Admins
        if ($rolloutTarget === null) {
            /** @var User $user */
            $user = $this->authManager->user();
            $asRoomRequest = !$user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
                && !$this->eventSettingsService->alwaysDirectBooking();
        }

        $this->seriesEventsService->applyDefinitionChange(
            $event,
            $series,
            $definitionInput,
            $propertyIds,
            $asRoomRequest,
            $rolloutTarget['templateEventId'] ?? null
        );
    }

    /**
     * Zurückgestellte Raumanfrage-Benachrichtigungen (propagateToSiblings) für die Termine senden, die noch
     * aktiv, weiter angefragt, nicht geplant und einem Raum zugeordnet sind.
     *
     * @param array<int, int> $eventIds
     */
    private function notifyRoomAdminsOfDeferredRequests(array $eventIds): void
    {
        if ($eventIds === []) {
            return;
        }

        // Läuft im finally: die Hilfsmethode sichert Abfrage und jeden Termin einzeln ab, ein Fehler hier
        // überdeckt den ursprünglichen nicht und kostet die übrigen Termine nicht ihre Meldung
        $this->roomRequestNotificationService->notifyRoomAdminsOfOpenRequests($eventIds);
    }

    /**
     * Stand der offenen Raumanfragen vor einer Massenänderung: Raum (Empfängerkreis) und alles, was in der
     * Anfrage-Meldung steht. Dient notifyRoomAdminsOfChangedOpenRequests() als Vergleich.
     *
     * @param array<int, int|string> $eventIds
     * @return array<int, string>
     */
    private function openRoomRequestSnapshot(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        return Event::query()
            ->whereIn('id', $eventIds)
            ->where('occupancy_option', true)
            ->get(['id', 'room_id', 'start_time', 'end_time', 'eventName', 'event_type_id', 'project_id'])
            ->mapWithKeys(static fn (Event $event): array => [
                (int) $event->getKey() => (string) json_encode($event->getAttributes()),
            ])
            ->all();
    }

    /**
     * Nach Serien-/Multi-Edit-/Bulk-Änderungen wie updateEvent bzw. moveEventsToCell: geänderte offene Anfragen
     * melden bzw. aktualisieren – bei Raumwechsel bekommen die Admins des neuen Raums die Anfrage, die des alten
     * verlieren sie. Unveränderte Anfragen (z. B. nur Status geändert) bleiben unberührt.
     *
     * @param array<int, string> $openRoomRequestsBefore
     */
    private function notifyRoomAdminsOfChangedOpenRequests(array $openRoomRequestsBefore): void
    {
        if ($openRoomRequestsBefore === []) {
            return;
        }

        $openRoomRequestsAfter = $this->openRoomRequestSnapshot(array_keys($openRoomRequestsBefore));
        $changedEventIds = array_keys(array_filter(
            $openRoomRequestsAfter,
            static fn (string $after, int $eventId): bool => $openRoomRequestsBefore[$eventId] !== $after,
            ARRAY_FILTER_USE_BOTH
        ));
        if ($changedEventIds === []) {
            return;
        }

        $this->roomRequestNotificationService->notifyRoomAdminsOfOpenRequests($changedEventIds);
    }

    /**
     * Schickt der Dialog eine vollständige Seriendefinition (Serie gewünscht + Turnus + Ende/Anzahl)?
     */
    private function requestsSeriesDefinition(Request $request): bool
    {
        // FALLE: EventUpdateRequest überschreibt data() mit eigenen Schlüsseln – filled()/boolean()/has()
        // laufen in Laravel über data() und sehen die Request-Felder daher NICHT. Nur input()/all() nutzen.
        $input = $request->all();
        if (!array_key_exists('is_series', $input)) {
            return false;
        }

        $wantsSeries = filter_var($input['is_series'], FILTER_VALIDATE_BOOLEAN);
        $hasDefinition = !empty($input['seriesFrequency'])
            && (!empty($input['seriesEndDate']) || !empty($input['seriesOccurrenceCount']));

        return $wantsSeries && $hasDefinition;
    }

    /**
     * Legt handleSeriesDefinitionOnUpdate() neue Serientermine an – und wenn ja: in welchem Raum und als welche
     * Art Termin (Raum/is_planning der Vorlage nach dieser Bearbeitung)?
     * - Einzeltermin -> Serie: Vorlage ist der bearbeitete Termin.
     * - Bestehende Serie: nur bei Reichweite „folgende“/„alle“ und wenn die Definitionsänderung neue Termine
     *   erzeugt; Vorlage wie in SeriesEventsService::applyDefinitionChange (Neu-Ausrollen: dieser Termin,
     *   sonst der letzte aktive Termin der Serie, der einen Raumwechsel per propagateToSiblings mitbekommt).
     *
     * @return array{roomId: ?int, isPlanning: bool, templateEventId: int}|null
     */
    private function seriesRolloutTarget(Request $request, Event $event, string $scope): ?array
    {
        if (!$this->requestsSeriesDefinition($request)) {
            return null;
        }

        $input = $request->all();
        $roomChanges = array_key_exists('roomId', $input) && (int) $input['roomId'] !== (int) $event->room_id;
        $targetRoomId = $roomChanges ? $input['roomId'] : $event->room_id;
        $editedEventTarget = [
            'roomId' => $targetRoomId ? (int) $targetRoomId : null,
            'isPlanning' => (bool) $event->is_planning,
            'templateEventId' => (int) $event->id,
        ];

        if (!$event->is_series) {
            return $editedEventTarget;
        }

        if ($scope === SeriesEventsService::SCOPE_SINGLE) {
            return null;
        }

        /** @var SeriesEvents|null $series */
        $series = SeriesEvents::query()->find($event->series_id);
        if (!$series) {
            return null;
        }

        $plan = $this->seriesEventsService->planDefinitionChange(
            $event,
            $series,
            $this->seriesDefinitionInput($request)
        );
        if (!$plan['changed'] || $plan['toCreate'] === []) {
            return null;
        }

        $template = $plan['rebuild'] ? $event : ($this->seriesEventsService->latestActiveSibling($series) ?? $event);
        if ($template->is($event)) {
            return $editedEventTarget;
        }

        $templateRoomId = $roomChanges ? $targetRoomId : $template->room_id;

        return [
            'roomId' => $templateRoomId ? (int) $templateRoomId : null,
            'isPlanning' => (bool) $template->is_planning,
            // An applyDefinitionChange durchreichen: nach der Zeitverschiebung kann ein anderer Termin der letzte sein
            'templateEventId' => (int) $template->id,
        ];
    }

    public function getTrashed(Request $request): Response|ResponseFactory
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('entitiesPerPage', 25);

        $trashedEvents = Event::onlyTrashed()
            ->with(['project', 'event_type', 'room'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
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
                // Serie als Ganzes aus dem Papierkorb holen (events.series.restore)
                'series_id' => $event->series_id,
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
        // Schichten löschen ist Dienstplanung — das Termin-Schreibrecht allein genügt nicht
        abort_unless(
            $this->authManager->user()?->can(PermissionEnum::SHIFT_PLANNER->value),
            403
        );

        // Wie alle Schicht-Aktionen: nur Schichten der Gewerke, die die Person planen darf
        app(CraftScopeService::class)->assertCanPlanShifts($this->authManager->user(), $event->shifts);

        app(ShiftDeletionService::class)->deleteMany($event->shifts);
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
        // Broadcasts nach der Response (hängender Websocket-Server bremst sonst den Client).
        // RemoveEvent sendet EventService::delete bereits (vorher doppelt).
        dispatch(static function () use ($event): void {
            SafeBroadcast::send(new BulkEventChanged($event, 'deleted'));
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
            // Alle Meldungen mit diesem Schlüssel, aber nur zu genau diesem Termin: dieselbe Meldung ging ggf. an
            // mehrere Empfänger:innen – deren „Termin löschen“ liefe sonst ins Leere (404). Fremde Termine mit
            // zufällig/manipuliert gleichem Schlüssel bleiben unberührt.
            $notifications = DatabaseNotification::query()
                ->whereRaw('JSON_VALID(data)')
                ->whereJsonContains("data->notificationKey", $request->notificationKey)
                ->where('data->eventId', (string) $event->id)
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
     * Serientermine in den Papierkorb legen – Reichweite: single | following | all (Default all).
     * @throws AuthorizationException
     */
    public function destroySeriesEvents(Event $event, Request $request): JsonResponse
    {
        if (!$event->is_series || !$event->series_id) {
            return response()->json(['trashed' => 0]);
        }

        $scope = $this->seriesEventsService->normalizeScope($request->input('scope', 'all'));
        if ($scope === SeriesEventsService::SCOPE_SINGLE) {
            $this->authorize('delete', $event);
        } else {
            $targets = $this->seriesEventsService
                ->siblingsQuery($event, $scope, Carbon::parse($event->start_time))
                ->get()
                ->push($event);
            foreach ($targets as $target) {
                $this->authorize('delete', $target);
            }
        }

        return response()->json(['trashed' => $this->seriesEventsService->trashScoped($event, $scope)]);
    }

    /**
     * Update all events in a series (room change and/or time shift)
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.TooHigh
    public function updateSeriesEvents(Event $event, Request $request): void
    {
        $this->authorize('update', $event);
        $request->validate([
            'newRoomId' => ['nullable', 'integer', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'calculationType' => ['nullable', 'integer', 'in:1,2'],
            'value' => ['nullable', 'integer', 'between:-10000,10000'],
            'type' => ['nullable', 'integer', 'between:1,5'],
        ]);
        if (!$event->is_series || !$event->series_id) {
            return;
        }

        $seriesEvents = Event::query()
            ->with(['project', 'room', 'creator'])
            ->where('series_id', $event->series_id)
            ->get();

        // Geändert wird die ganze Serie: jeden Termin autorisieren (wie destroySeriesEvents) und beim
        // Raumwechsel das Buchungsrecht im Zielraum verlangen – verschoben wird ohne Anfrage-Workflow.
        $newRoom = $request->get('newRoomId') !== null
            ? Room::query()->findOrFail($request->integer('newRoomId'))
            : null;
        $checkedRooms = [];
        foreach ($seriesEvents as $seriesEvent) {
            $this->authorize('update', $seriesEvent);
            if ($newRoom !== null && (int) $seriesEvent->room_id !== (int) $newRoom->id) {
                $this->authorizeBulkEventCreationOnce($checkedRooms, (bool) $seriesEvent->is_planning, $newRoom);
            }
        }

        $openRoomRequestsBefore = $this->openRoomRequestSnapshot($seriesEvents->modelKeys());

        // Alles oder nichts: ein Fehler mitten in der Serie darf sie nicht halb verschoben zurücklassen.
        DB::transaction(function () use ($seriesEvents, $request): void {
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
                                $shift->setAttribute(
                                    'start_date',
                                    Carbon::parse($shift->getAttribute('start_date'))->addDays($value)
                                );
                                $shift->setAttribute(
                                    'end_date',
                                    Carbon::parse($shift->getAttribute('end_date'))->addDays($value)
                                );
                                $shift->save();
                            }
                        }
                        if ($type === 3) {
                            $seriesEvent->setAttribute('start_time', $startDate->addWeeks($value));
                            $seriesEvent->setAttribute('end_time', $endDate->addWeeks($value));
                            foreach ($shifts as $shift) {
                                $shift->setAttribute(
                                    'start_date',
                                    Carbon::parse($shift->getAttribute('start_date'))->addWeeks($value)
                                );
                                $shift->setAttribute(
                                    'end_date',
                                    Carbon::parse($shift->getAttribute('end_date'))->addWeeks($value)
                                );
                                $shift->save();
                            }
                        }
                        if ($type === 4) {
                            $seriesEvent->setAttribute('start_time', $startDate->addMonths($value));
                            $seriesEvent->setAttribute('end_time', $endDate->addMonths($value));
                            foreach ($shifts as $shift) {
                                $shift->setAttribute(
                                    'start_date',
                                    Carbon::parse($shift->getAttribute('start_date'))->addMonths($value)
                                );
                                $shift->setAttribute(
                                    'end_date',
                                    Carbon::parse($shift->getAttribute('end_date'))->addMonths($value)
                                );
                                $shift->save();
                            }
                        }
                        if ($type === 5) {
                            $seriesEvent->setAttribute('start_time', $startDate->addYears($value));
                            $seriesEvent->setAttribute('end_time', $endDate->addYears($value));
                            foreach ($shifts as $shift) {
                                $shift->setAttribute(
                                    'start_date',
                                    Carbon::parse($shift->getAttribute('start_date'))->addYears($value)
                                );
                                $shift->setAttribute(
                                    'end_date',
                                    Carbon::parse($shift->getAttribute('end_date'))->addYears($value)
                                );
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
                                $shift->setAttribute(
                                    'start_date',
                                    Carbon::parse($shift->getAttribute('start_date'))->subDays($value)
                                );
                                $shift->setAttribute(
                                    'end_date',
                                    Carbon::parse($shift->getAttribute('end_date'))->subDays($value)
                                );
                                $shift->save();
                            }
                        }
                        if ($type === 3) {
                            $seriesEvent->setAttribute('start_time', $startDate->subWeeks($value));
                            $seriesEvent->setAttribute('end_time', $endDate->subWeeks($value));
                            foreach ($shifts as $shift) {
                                $shift->setAttribute(
                                    'start_date',
                                    Carbon::parse($shift->getAttribute('start_date'))->subWeeks($value)
                                );
                                $shift->setAttribute(
                                    'end_date',
                                    Carbon::parse($shift->getAttribute('end_date'))->subWeeks($value)
                                );
                                $shift->save();
                            }
                        }
                        if ($type === 4) {
                            $seriesEvent->setAttribute('start_time', $startDate->subMonths($value));
                            $seriesEvent->setAttribute('end_time', $endDate->subMonths($value));
                            foreach ($shifts as $shift) {
                                $shift->setAttribute(
                                    'start_date',
                                    Carbon::parse($shift->getAttribute('start_date'))->subMonths($value)
                                );
                                $shift->setAttribute(
                                    'end_date',
                                    Carbon::parse($shift->getAttribute('end_date'))->subMonths($value)
                                );
                                $shift->save();
                            }
                        }
                        if ($type === 5) {
                            $seriesEvent->setAttribute('start_time', $startDate->subYears($value));
                            $seriesEvent->setAttribute('end_time', $endDate->subYears($value));
                            foreach ($shifts as $shift) {
                                $shift->setAttribute(
                                    'start_date',
                                    Carbon::parse($shift->getAttribute('start_date'))->subYears($value)
                                );
                                $shift->setAttribute(
                                    'end_date',
                                    Carbon::parse($shift->getAttribute('end_date'))->subYears($value)
                                );
                                $shift->save();
                            }
                        }
                    }
                }

                $seriesEvent->save();
            }
        });

        $this->notifyRoomAdminsOfChangedOpenRequests($openRoomRequestsBefore);

        foreach ($seriesEvents as $seriesEvent) {
            $freshSeriesEvent = $seriesEvent->fresh();
            SafeBroadcast::send(new EventCreated($freshSeriesEvent, $freshSeriesEvent->room_id));
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
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService
    ): RedirectResponse {
        /** @var Event $event */
        $event = Event::onlyTrashed()->findOrFail($id);

        $this->authorize('delete', $event);

        // Stale Projekt-Zeiger vor dem Restore bereinigen: EventService::restore
        // greift bei gesetzter project_id auf $event->project->id zu und würde bei
        // einem zwischenzeitlich gelöschten Projekt crashen.
        if ($event->project_id && !$event->project()->exists()) {
            $event->project_id = null;
            $event->saveQuietly();
        }

        // Über den Service wiederherstellen, damit auch die mitgetrashten
        // Schichten (inkl. shift_workers), Timelines, Kommentare und SubEvents
        // zurückkommen — nicht nur das Event selbst. Die beim Löschen geschlossene
        // Raumanfrage öffnet der Service wieder (wie bei Projekt-/Serien-Wiederherstellung).
        $this->eventService->restore(
            $event,
            $shiftsQualificationsService,
            $changeService,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService
        );

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
        // Erst ALLE Termine autorisieren, dann löschen – sonst wären bei einem 403 mitten in der
        // Auswahl die ersten Termine schon gelöscht.
        $events = [];
        foreach ($request->collect('events') as $eventId) {
            $event = $eventService->findEventById($eventId);

            if ($event === null) {
                continue;
            }
            $this->authorize('delete', $event);
            $events[] = $event;
        }

        foreach ($events as $event) {
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
        $events = $this->authorizedMultiEditEvents($eventIds, $this->multiEditTargetRoom($request), false);
        $openRoomRequestsBefore = $this->openRoomRequestSnapshot(
            array_map(static fn (Event $event): int => (int) $event->getKey(), $events)
        );

        foreach ($events as $event) {
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
            $freshEvent = $event->fresh();
            SafeBroadcast::send(new EventCreated($freshEvent, $freshEvent->room_id));
        }

        $this->notifyRoomAdminsOfChangedOpenRequests($openRoomRequestsBefore);

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
        // Kopien werden fest gebucht (Buchungsstatus des Originals, kein Anfrage-Workflow) – deshalb wie
        // bei duplicateEventsToCells Buchungsrecht im Zielraum (neuer Raum oder Raum des Originals) nötig.
        $originalEvents = $this->authorizedMultiEditEvents(
            $eventIds,
            $this->multiEditTargetRoom($request),
            true,
            $forcePlanning
        );

        foreach ($originalEvents as $originalEvent) {
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
            $freshEvent = $event->fresh();
            SafeBroadcast::send(new EventCreated($freshEvent, $freshEvent->room_id));
        }
    }


    /**
     * Zielraum der Mehrfachbearbeitung im Kalender (newRoomId). Unbekannte Räume enden vor jeder Änderung in 404.
     */
    private function multiEditTargetRoom(Request $request): ?Room
    {
        if ($request->get('newRoomId') === null) {
            return null;
        }

        return Room::query()->findOrFail($request->integer('newRoomId'));
    }

    /**
     * Lädt und autorisiert ALLE Termine der Mehrfachbearbeitung, bevor etwas geändert wird – sonst blieben
     * bei einem 403 mitten in der Auswahl die ersten Termine schon geändert bzw. kopiert zurück.
     * Verschieben in einen anderen Raum und Kopieren buchen direkt (kein Anfrage-Workflow), deshalb gilt
     * dafür dieselbe Raumprüfung wie beim Bulk-Anlegen.
     *
     * @param \Illuminate\Support\Collection<int, mixed> $eventIds
     * @param bool $createsCopies true = Duplizieren: Zielraum ist der neue Raum oder der Raum des Originals
     * @param bool $forcePlanning Kopien werden geplante Termine (Planungskalender)
     * @return array<int, Event>
     */
    private function authorizedMultiEditEvents(
        \Illuminate\Support\Collection $eventIds,
        ?Room $targetRoom,
        bool $createsCopies,
        bool $forcePlanning = false
    ): array {
        // Eine Abfrage mit den Relationen, die Policy und Raumprüfung lesen (statt Lazy-Loads je Termin)
        $loadedEvents = Event::query()
            ->with(['project', 'room', 'creator'])
            ->whereIn('id', $eventIds->map(static fn ($eventId): int => (int) $eventId)->all())
            ->get()
            ->keyBy('id');

        $events = [];
        $checkedRooms = [];
        foreach ($eventIds as $eventId) {
            $event = $loadedEvents->get((int) $eventId);

            if ($event === null) {
                continue;
            }
            $this->authorize('update', $event);

            $isPlanning = $forcePlanning || (bool) $event->is_planning;
            if ($targetRoom !== null) {
                if ($createsCopies || (int) $event->room_id !== (int) $targetRoom->id) {
                    $this->authorizeBulkEventCreationOnce($checkedRooms, $isPlanning, $targetRoom);
                }
            } elseif ($createsCopies) {
                $this->authorizeCopyIntoOriginalRoom($event, $isPlanning, $checkedRooms);
            }

            $events[] = $event;
        }

        return $events;
    }

    /**
     * Kopie ohne neuen Zielraum landet im Raum des Originals:
     * - Raum im Papierkorb: keine Kopie (sonst Geistertermin in einem gelöschten Raum, vgl. duplicateEventsToCells)
     * - ohne Raum: dieselbe Prüfung wie „Termin ohne Raum anlegen“ (storeEvent)
     * - sonst: Buchungsrecht im Raum wie beim Bulk-Anlegen
     *
     * @param array<string, true> $checkedRooms
     * @throws ValidationException
     */
    private function authorizeCopyIntoOriginalRoom(Event $event, bool $isPlanning, array &$checkedRooms): void
    {
        if ($event->room_id === null) {
            $key = ($isPlanning ? 'planning:' : 'regular:') . 'without-room';
            if (!isset($checkedRooms[$key])) {
                /** @var User $user */
                $user = $this->authManager->user();
                $this->resolveRoomBookingOption($user, null, $isPlanning, false);
                $checkedRooms[$key] = true;
            }

            return;
        }

        // Die Relation lädt Räume im Papierkorb nicht
        $room = $event->room;
        if ($room === null) {
            throw ValidationException::withMessages([
                'newRoomId' => __('The room of this event is in the trash. Choose a new room for the copy.'),
            ]);
        }

        $this->authorizeBulkEventCreationOnce($checkedRooms, $isPlanning, $room);
    }

    /**
     * authorizeBulkEventCreation() je (Termin-Art, Raum) nur einmal pro Request auswerten.
     *
     * @param array<string, true> $checkedRooms bereits erlaubte Kombinationen
     */
    private function authorizeBulkEventCreationOnce(array &$checkedRooms, bool $isPlanning, Room $room): void
    {
        $key = ($isPlanning ? 'planning:' : 'regular:') . $room->id;
        if (isset($checkedRooms[$key])) {
            return;
        }

        $this->authorizeBulkEventCreation($isPlanning, $room);
        $checkedRooms[$key] = true;
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

        // "Termine immer direkt buchbar": "Raumbelegungen anfragen" bedeutet dann "Termine anlegen"
        // und schließt Bulk-Anlage und Serien ein (Entscheidung 18.09.2026).
        $mayCreateRegularEvent = $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value)
            || ($this->eventSettingsService->alwaysDirectBooking()
                && $user->can(PermissionEnum::EVENT_REQUEST->value))
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
                SafeBroadcast::send(new \Artwork\Modules\Event\Events\BulkEventChanged($freshEvent, 'created'));
                SafeBroadcast::send(new EventCreated($freshEvent, $freshEvent->room_id));
            }
        })->afterResponse();

        // Erstellte Events zurückgeben: der auslösende Client aktualisiert seine Liste
        // aus der Response und ist damit nicht auf den eigenen Broadcast angewiesen.
        return new JsonResponse(['events' => $createdEventPayloads]);
    }

    public function updateSingleBulkEvent(
        Request $request,
        Event $event
    ): JsonResponse {
        $this->authorize('update', $event);

        $data =  $request->collect('data');

        // Raumwechsel bucht direkt (kein Anfrage-Workflow) – wie beim Bulk-Anlegen nur mit Buchungsrecht im Zielraum
        $newRoomId = data_get($data, 'room.id');
        if ($newRoomId !== null && (int) $newRoomId !== (int) $event->room_id) {
            $this->authorizeBulkEventCreation(
                $this->eventService->resolveBulkIsPlanning($data, $event),
                Room::query()->findOrFail($newRoomId)
            );
        }

        $openRoomRequestsBefore = $this->openRoomRequestSnapshot([(int) $event->id]);
        $this->eventService->updateBulkEvent(
            $data,
            $event
        );
        $this->notifyRoomAdminsOfChangedOpenRequests($openRoomRequestsBefore);

        $freshEvent = $event->fresh();
        // Broadcasts nach der Response — hängender Websocket-Server darf den Patch nicht bremsen
        dispatch(static function () use ($freshEvent): void {
            SafeBroadcast::send(new \Artwork\Modules\Event\Events\BulkEventChanged($freshEvent, 'updated'));
            SafeBroadcast::send(new EventUpdated($freshEvent, $freshEvent->room_id));
        })->afterResponse();

        // Aktualisiertes Event zurückgeben: der auslösende Client setzt die
        // "zuletzt bearbeitet"-Markierung sofort aus der Response (Server-updated_at),
        // statt auf den eigenen Broadcast-Roundtrip zu warten.
        return new JsonResponse([
            'event' => \Artwork\Modules\Event\Events\BulkEventChanged::eventPayload($freshEvent),
        ]);
    }

    public function createSingleBulkEvent(
        Request $request,
        Project $project
    ): JsonResponse {
        $this->authorize('view', $project);

        $request->validate([
            'event' => ['required', 'array'],
            'event.room.id' => ['required', 'integer', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
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
            SafeBroadcast::send(new \Artwork\Modules\Event\Events\BulkEventChanged($freshEvent, 'created'));
            SafeBroadcast::send(new EventCreated($freshEvent, $freshEvent->room_id));
        })->afterResponse();

        // Erstelltes Event zurückgeben: der auslösende Client aktualisiert seine Liste
        // aus der Response und ist damit nicht auf den eigenen Broadcast angewiesen.
        return new JsonResponse([
            'event' => \Artwork\Modules\Event\Events\BulkEventChanged::eventPayload($freshEvent),
        ]);
    }

    /**
     * Beschreibung eines einzelnen Termins. Der Kalender liefert den Volltext nur
     * noch mit, wenn die Anzeigeeinstellung ihn in der Kachel zeigt — das Termin-Modal
     * holt ihn hier nach.
     *
     * @throws AuthorizationException
     */
    public function showDescription(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return new JsonResponse(['description' => $event->description]);
    }

    public function updateDescription(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event->update($request->only(['description']));

        $freshEvent = $event->fresh();
        dispatch(static function () use ($freshEvent): void {
            // Auch die Bulk-Terminliste anderer Sessions aktualisieren (inkl.
            // "zuletzt bearbeitet"-Markierung) — vorher fehlte dieser Broadcast.
            SafeBroadcast::send(new BulkEventChanged($freshEvent, 'updated'));
            SafeBroadcast::send(new EventCreated($freshEvent, $freshEvent->room_id));
        })->afterResponse();

        return new JsonResponse([
            'event' => BulkEventChanged::eventPayload($freshEvent),
        ]);
    }


    public function bulkMultiEditEvent(Request $request): void
    {
        $eventIds = $request->collect('eventIds');

        $selectedRoomId = data_get($request->input('selectedRoom'), 'id');
        $selectedRoom = $selectedRoomId !== null ? Room::query()->findOrFail($selectedRoomId) : null;

        foreach (Event::whereIn('id', $eventIds)->get() as $eventToAuthorize) {
            $this->authorize('update', $eventToAuthorize);

            // Raumwechsel bucht direkt (kein Anfrage-Workflow) – Buchungsrecht im Zielraum nötig
            if ($selectedRoom !== null && (int) $eventToAuthorize->room_id !== (int) $selectedRoom->id) {
                $this->authorizeBulkEventCreation((bool) $eventToAuthorize->is_planning, $selectedRoom);
            }
        }

        $openRoomRequestsBefore = $this->openRoomRequestSnapshot(
            $eventIds->map(static fn ($id): int => (int) $id)->all()
        );
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

        $this->notifyRoomAdminsOfChangedOpenRequests($openRoomRequestsBefore);

        // Broadcasts nach der Response (hängender Websocket-Server bremst sonst den Client) – einzige Stelle,
        // das Repository sendet nicht mehr selbst (vorher doppelt)
        $events = Event::whereIn('id', $eventIds)->get();
        dispatch(static function () use ($events): void {
            foreach ($events as $event) {
                SafeBroadcast::send(new BulkEventChanged($event, 'updated'));
                SafeBroadcast::send(new EventCreated($event, $event->room_id));
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
                    SafeBroadcast::send(new RemoveEvent($event, $event->room_id));
                }
                SafeBroadcast::send(new BulkEventChanged($event, 'deleted'));
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
            'cells.*.room_id' => ['required', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'event_type_id' => ['required', 'exists:event_types,id'],
            'event_status_id' => ['nullable', 'exists:event_statuses,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'is_planning' => ['nullable', 'boolean'],
        ]);

        // Projektzuordnung bewusst ohne eigene Prüfung, siehe storeEvent()

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
            'cells.*.room_id' => ['required', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
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
            'cell.room_id' => ['required', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
        ]);

        $room = Room::query()->findOrFail($validated['cell']['room_id']);
        $events = Event::query()
            ->with(['shifts', 'project', 'room', 'creator'])
            ->whereIn('id', $validated['events'])
            ->get();

        // Ein Raumwechsel platziert den Termin direkt im Zielraum (ohne Anfrage-Workflow) und braucht dort
        // Buchungsrecht – reines Datumsverschieben im selben Raum nicht (wie updateMultiEdit).
        // Wie bei den Geschwister-Endpunkten erst ALLE Termine autorisieren.
        $checkedRooms = [];
        foreach ($events as $event) {
            $this->authorize('update', $event);
            if ((int) $event->getAttribute('room_id') !== (int) $room->getAttribute('id')) {
                $this->authorizeBulkEventCreationOnce(
                    $checkedRooms,
                    (bool) $event->getAttribute('is_planning'),
                    $room
                );
            }
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

        // Wie beim Einzeltermin: offene Anfragen melden bzw. aktualisieren (notifyRoomAdmins ist idempotent) –
        // bei Raumwechsel erhalten die Admins des neuen Raums die Anfrage und die des alten verlieren sie, beim
        // reinen Datumswechsel zeigt die bestehende Meldung danach das neue Datum. Geplante Termine fragen erst
        // beim Umstellen an. Je Termin abgesichert: die Termine sind schon verschoben.
        foreach ($movedEvents as $movedEvent) {
            if (!$movedEvent->getAttribute('occupancy_option') || $movedEvent->getAttribute('is_planning')) {
                continue;
            }

            try {
                $movedEvent->unsetRelation('room');
                $this->roomRequestNotificationService->notifyRoomAdmins($movedEvent);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

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
                static fn (Event $event) => SafeBroadcast::send(new EventCreated($event, $event->room_id))
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

    public function standardEventValues(DirectBookingActivationService $directBookingActivationService)
    {
        // Zähler für die Warnung beim Aktivieren von "Termine immer direkt buchbar"
        return Inertia::render('Settings/StandardEventValues', [
            'openRoomRequestsCount' => $directBookingActivationService->openRoomRequestsCount(),
            'pendingVerificationsCount' => $directBookingActivationService->pendingVerificationsCount(),
        ]);
    }

    public function saveStandardEventValues(Request $request): void
    {
        // Standardwerte nur übernehmen, wenn sie mitgeschickt werden – der Schalter
        // "Termine immer direkt buchbar" wird getrennt gespeichert (nur sein Feld im Request).
        if ($request->has('event_time_length_minutes')) {
            $this->generalSettingsService->updateEventTimeLengthMinutesFromRequest($request);
        }
        if ($request->has('event_start_time')) {
            $this->generalSettingsService->updateEventStartTimeFromRequest($request);
        }
        if ($request->has('event_all_day_default')) {
            $this->generalSettingsService->updateEventAllDayDefaultFromRequest($request);
        }

        // Einlass-Feld instanzweit aktivieren/deaktivieren (kein Standardwert,
        // sondern Modul-Schalter — Werte bleiben beim Deaktivieren erhalten)
        if ($request->has('enable_admission')) {
            $eventSettings = app(EventSettings::class);
            $eventSettings->enable_admission = $request->boolean('enable_admission');
            $eventSettings->save();
        }

        // "Termine immer direkt buchbar": beim Einschalten werden offene Raumanfragen und
        // Verifizierungsanfragen übernommen (Warnung im Frontend), Menü-Caches werden geleert.
        if ($request->has('always_direct_booking')) {
            app(DirectBookingActivationService::class)->apply($request->boolean('always_direct_booking'));
        }
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
        SafeBroadcast::send(new EventUpdated(
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
        // Das Grid des Schichtplans baut seine Zeilen aus shifts.workers, nicht aus
        // den Craft-Relationen — dort waren users/freelancers/serviceProviders 95%
        // des Payloads (1,25 MB), ohne gelesen zu werden.
        // ABER: die Tagesansicht reicht diese crafts an SingleShiftInDailyShiftView
        // weiter, dessen getAssignablePeople() die Personen zum Zuweisen von
        // Schichtplätzen daraus zieht. Sie fordert sie deshalb per withPeople an.
        $eagerLoad = [
            'qualifications:id,name,icon,available',
        ];

        if ($request->boolean('withPeople')) {
            $eagerLoad[] = 'users:id,first_name,last_name,position,profile_photo_path';
            $eagerLoad[] = 'freelancers:id,first_name,last_name,position,profile_image';
            $eagerLoad[] = 'serviceProviders:id,provider_name,profile_image';
        }

        if (!$request->boolean('lightweight')) {
            // Nur die id wird gelesen; die übrigen Spalten brauchen die $appends
            // der Modelle beim Serialisieren (siehe viewShiftPlan). Mit reinem
            // ':id' warf ServiceProvider::getNameAttribute() einen TypeError,
            // sobald überhaupt eine Gewerksleitung hinterlegt war.
            $eagerLoad[] = 'managingUsers:id,first_name,last_name,profile_photo_path,work_time_balance';
            $eagerLoad[] = 'managingFreelancers:id,first_name,last_name,profile_image';
            $eagerLoad[] = 'managingServiceProviders:id,provider_name,profile_image';
        }

        // craftShiftPlaner bleibt bewusst drin: die Lookups des Schichtplans
        // speisen RequestWorkTimeChangeModal, das die Planer-Liste anzeigt.
        $crafts = Craft::query()
            ->with($eagerLoad)
            ->orderBy('position')
            ->get();

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
            // workTimeBalance (Altformat) + workTimeBalanceFormatted/-Minutes (AZK-Badge mit Vorzeichen)
            $additionalData = array_merge(
                $additionalData,
                $this->workingHourService->workTimeBalanceData($worker, $showHours)
            );
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
                $workerData,
                $workerId,
                $startDate,
                $endDate,
                $worker
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

    /**
     * @throws AuthorizationException
     */
    public function getTimelines(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $event->load('timelines');

        return response()->json([
            'event' => $event,
            'timelines' => $event->timelines
        ]);
    }
}
