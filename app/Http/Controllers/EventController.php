<?php

namespace App\Http\Controllers;

use App\Casts\TimeAgoCast;
use App\Enums\NotificationConstEnum;
use App\Events\OccupancyUpdated;
use App\Http\Requests\EventStoreRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Resources\CalendarEventResource;
use App\Http\Resources\EventShowResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\FreelancerShiftPlanResource;
use App\Http\Resources\ResourceModels\CalendarEventCollectionResourceModel;
use App\Http\Resources\ServiceProviderShiftPlanResource;
use App\Http\Resources\TaskDashboardResource;
use App\Http\Resources\UserShiftPlanResource;
use App\Models\Craft;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Filter;
use App\Models\Freelancer;
use App\Models\SeriesEvents;
use App\Models\ServiceProvider;
use App\Models\Task;
use App\Models\User;
use App\Models\UserCalendarFilter;
use App\Models\UserShiftCalendarFilter;
use App\Sage100\Sage100;
use App\Support\Services\CollisionService;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Sage100\Services\Sage100Service;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;
use const _PHPStan_11268e5ee\__;

class EventController extends Controller
{
    protected ?\stdClass $notificationData = null;

    protected ?NewHistoryService $history = null;

    protected ?string $notificationKey = '';

    private $user;

    private UserShiftCalendarFilter $userShiftCalendarFilter;

    private UserCalendarFilter $userCalendarFilter;

    public function __construct(
        private readonly CollisionService $collisionService,
        private readonly NotificationService $notificationService,
        private readonly BudgetService $budgetService,
    ) {
        $this->notificationData = new \stdClass();
        $this->notificationData->event = new \stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_EVENT_CHANGED;
        $this->history = new NewHistoryService(Event::class);

        $this->notificationKey = Str::random(15);
    }

    public function viewEventIndex(CalendarController $calendarController): Response
    {
        $this->user = Auth::user();
        $this->userCalendarFilter = $this->user->calendar_filter()->first();
        $this->userShiftCalendarFilter = $this->user->shift_calendar_filter()->first();
        $events = [];
        if (!is_null($this->userCalendarFilter->start_date) && !is_null($this->userCalendarFilter->end_date)) {
            $showCalendar = $calendarController->createCalendarData(
                'individual',
                null,
                null,
                $this->userCalendarFilter->start_date,
                $this->userCalendarFilter->end_date
            );

            $eventsOfDay = collect();

            if ($this->userCalendarFilter->start_date === $this->userCalendarFilter->end_date) {

                $eventsOfDay = Collection::make(CalendarEventResource::collection($calendarController
                    ->getEventsOfInterval(
                        $this->userCalendarFilter->start_date,
                        $this->userCalendarFilter->end_date
                    ))->resolve());
            }

            $events = new CalendarEventCollectionResourceModel(
                areas: $showCalendar['filterOptions']['areas'],
                projects: $showCalendar['filterOptions']['projects'],
                eventTypes: $showCalendar['filterOptions']['eventTypes'],
                roomCategories: $showCalendar['filterOptions']['roomCategories'],
                roomAttributes: $showCalendar['filterOptions']['roomAttributes'],
                events: $eventsOfDay,
                filter: Filter::where('user_id', Auth::id())->get(),
            );
        } else {
            $showCalendar = $calendarController->createCalendarData();
        }

        $eventsAtAGlance = [];

        if (!is_null($this->userCalendarFilter->start_date) && !is_null($this->userCalendarFilter->end_date)) {
            $startDate = Carbon::create($this->userCalendarFilter->start_date)->startOfDay();
            $endDate = Carbon::create($this->userCalendarFilter->end_date)->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }

        if (\request('atAGlance') === 'true') {
            $eventsAtAGlance = $calendarController->getEventsAtAGlance($startDate, $endDate);
        }

        return inertia('Events/EventManagement', [
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'calendar' => $showCalendar['roomsWithEvents'],
            'days' => $showCalendar['days'],
            'dateValue' => $showCalendar['dateValue'],
            'calendarType' => $showCalendar['calendarType'],
            'selectedDate' => $showCalendar['selectedDate'],
            'eventsWithoutRoom' => $showCalendar['eventsWithoutRoom'],
            'eventsAtAGlance' => $eventsAtAGlance,
            'rooms' => $calendarController->filterRooms($startDate, $endDate)->get(),
            'events' => $events,
            'filterOptions' => $showCalendar["filterOptions"],
            'personalFilters' => $showCalendar['personalFilters'],
            'user_filters' => $showCalendar['user_filters'],
        ]);
    }

    public function viewShiftPlan(
        CalendarController $shiftPlan,
        ShiftQualificationService $shiftQualificationService
    ): Response {
        $this->user = Auth::user();
        $this->userShiftCalendarFilter = $this->user->shift_calendar_filter()->first();
        $showCalendar = $shiftPlan->createCalendarDataForShiftPlan($this->userShiftCalendarFilter);

        $shiftFilterController = new ShiftFilterController();
        $shiftFilters = $shiftFilterController->index();

        if (
            !is_null($this->userShiftCalendarFilter?->start_date) &&
            !is_null($this->userShiftCalendarFilter?->end_date)
        ) {
            $startDate = Carbon::create($this->userShiftCalendarFilter->start_date)->startOfDay();
            $endDate = Carbon::create($this->userShiftCalendarFilter->end_date)->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }

        $events = Event::with(['shifts', 'event_type', 'room'])
            ->whereHas('shifts', function ($query): void {
                $query->whereNotNull('shifts.id')->without('crafts');
            })->whereBetween('start_time', [$startDate, $endDate])->without(['series'])
            ->get();
        $usersWithPlannedWorkingHours = [];

        //get the diff of startDate and endDate in days, +1 to include the current date
        $diffInDays = $startDate->diffInDays($endDate) + 1;

        /** @var User $user */
        foreach (
            User::where('can_work_shifts', true)
                ->without(['roles', 'permissions', 'calendar_settings'])
                ->get() as $user
        ) {
            $plannedWorkingHours = $user->plannedWorkingHours($startDate, $endDate);
            $expectedWorkingHours = ($user->weekly_working_hours / 7) * $diffInDays;

            $usersWithPlannedWorkingHours[] = [
                'user' => UserShiftPlanResource::make($user)
                    ->setStartDate($startDate)
                    ->setEndDate($endDate),
                'plannedWorkingHours' => $plannedWorkingHours,
                'expectedWorkingHours' => $expectedWorkingHours,
                'vacations' => $user->hasVacationDays(),
                'availabilities' => $user->availabilities()
                    ->whereBetween('date', [$startDate, $endDate])->get()
                    ->groupBy('formatted_date'),
            ];
        }

        $freelancersWithPlannedWorkingHours = [];

        $freelancers = Freelancer::where('can_work_shifts', true)->get();

        /** @var Freelancer $freelancer */
        foreach ($freelancers as $freelancer) {
            $plannedWorkingHours = $freelancer->plannedWorkingHours($startDate, $endDate);
            $freelancersWithPlannedWorkingHours[] = [
                'freelancer' => FreelancerShiftPlanResource::make($freelancer)
                    ->setStartDate($startDate)
                    ->setEndDate($endDate),
                'vacations' => $freelancer->hasVacationDays(),
                'plannedWorkingHours' => $plannedWorkingHours,
                'availabilities' => $freelancer->availabilities()
                    ->whereBetween('date', [$startDate, $endDate])->get()
                    ->groupBy('formatted_date'),
            ];
        }

        $historyElements = $events->flatMap(function ($event) {
            return $event->shifts->flatMap(function ($shift) {
                // Sort each shift's historyChanges by created_at in descending order
                return $shift->historyChanges()->sortByDesc('created_at');
            });
        })->all();

        $serviceProvidersWithPlannedWorkingHours = [];

        /** @var ServiceProvider $serviceProvider */
        foreach (ServiceProvider::where('can_work_shifts', true)->get() as $serviceProvider) {
            $plannedWorkingHours = $serviceProvider->plannedWorkingHours($startDate, $endDate);

            $serviceProvidersWithPlannedWorkingHours[] = [
                'service_provider' => ServiceProviderShiftPlanResource::make($serviceProvider)
                    ->setStartDate($startDate)
                    ->setEndDate($endDate),
                'plannedWorkingHours' => $plannedWorkingHours,
            ];
        }

        return inertia('Shifts/ShiftPlan', [
            'events' => $events,
            'crafts' => Craft::all(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => Project::all(),
            'shiftPlan' => $showCalendar['roomsWithEvents'],
            'rooms' => $shiftPlan->filterRooms($startDate, $endDate)->get(),
            'days' => $showCalendar['days'],
            'filterOptions' => $showCalendar['filterOptions'],
            'user_filters' => $showCalendar['user_filters'],
            'dateValue' => $showCalendar['dateValue'],
            'personalFilters' => $shiftFilters,
            'selectedDate' => $showCalendar['selectedDate'],
            'usersForShifts' => $usersWithPlannedWorkingHours,
            'freelancersForShifts' => $freelancersWithPlannedWorkingHours,
            'serviceProvidersForShifts' => $serviceProvidersWithPlannedWorkingHours,
            'history' => $historyElements,
            'shiftQualifications' => $shiftQualificationService->getAllOrderedByCreationDateAscending()
        ]);
    }

    public function showDashboardPage(): Response
    {
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

        $shiftsOfDay = $user
            ->shifts()
            ->whereDate(
                'event_start_day',
                Carbon::now()->format('Y-m-d')
            )->with(['event','event.project','event.room'])->get();

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
            )->with(['project', 'room'])->get();

        //get date for humans of today with weekday
        $todayDate = Carbon::now()->locale(\session()->get('locale') ?? config('app.fallback_locale'))->isoFormat('dddd, DD.MM.YYYY');

        $notification = $user
            ->notifications()
            ->select(['id', 'data->priority as priority', 'data'])
            ->whereDate('created_at', Carbon::now()->format('Y-m-d'))
            ->withCasts(['created_at' => TimeAgoCast::class])
            ->where('read_at', null);

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
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
            }

            //dd($historyObjects);

            if (request('historyType') === 'event') {
                $event = Event::find(request('modelId'));
                $historyComplete = $event->historyChanges()->all();
                foreach ($historyComplete as $history) {
                    $historyObjects[] = [
                        'changes' => json_decode($history->changes),
                        'created_at' => $history->created_at->diffInHours() < 24
                            ? $history->created_at->diffForHumans()
                            : $history->created_at->format('d.m.Y, H:i'),
                    ];
                }
            }
        }

        return inertia('Dashboard', [
            'tasks' => TaskDashboardResource::collection($tasks)->resolve(),
            'shiftsOfDay' => $shiftsOfDay,
            'todayDate' => $todayDate,
            'eventsOfDay' => $userEvents,
            'notificationOfToday' => $notification->get()->groupBy('priority'),
            'notificationCount' => $notification->count(),
            'event' => $event !== null ? new CalendarEventResource($event) : null,
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'rooms' => Room::all(),
            'historyObjects' => $historyObjects,
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
        ]);
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function storeEvent(EventStoreRequest $request): CalendarEventResource
    {
        $this->authorize('create', Event::class);

        $firstEvent = Event::create($request->data());
        $this->adjoiningRoomsCheck($request, $firstEvent);
        if ($request->get('projectName')) {
            $this->associateProject($request, $firstEvent, $this->budgetService);
        }

        $projectFirstEvent = $firstEvent->project()->first();

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

        if (!empty($firstEvent->project()->get())) {
            $eventProject = $firstEvent->project()->first();

            if ($eventProject) {
                $projectHistory = new NewHistoryService('Artwork\Modules\Project\Models\Project');
                $projectHistory->createHistory($eventProject->id, 'Ablaufplan hinzugefügt');
            }
        }

        if ($request->isOption) {
            $this->createRequestNotification($request, $firstEvent);
        }

        broadcast(new OccupancyUpdated())->toOthers();

        return new CalendarEventResource($firstEvent);
    }

    private function createSeriesEvent($startDate, $endDate, $request, $series, $projectId): void
    {
        Event::create([
            'name' => $request->title,
            'eventName' => $request->eventName,
            'description' => $request->description,
            'start_time' => $startDate,
            'end_time' => $endDate,
            'occupancy_option' => $request->isOption,
            'audience' => $request->audience,
            'is_loud' => $request->isLoud,
            'event_type_id' => $request->eventTypeId,
            'room_id' => $request->roomId,
            'user_id' => Auth::id(),
            'project_id' => $projectId ?: null,
            'is_series' => true,
            'series_id' => $series->id,
            'allDay' => $request->allDay
        ]);
    }

    public function commitShifts(Request $request): void
    {
        foreach ($request->events as $event) {
            $shiftIds = [];

            // Loop through each shift and collect the IDs
            foreach ($event['shifts'] as $shift) {
                $shiftIds[] = $shift['id'];
            }

            $shifts = Shift::whereIn('id', $shiftIds)->get();

            // get first shift in shifts
            $firstShift = $shifts->first();

            // get last shift in Shifts
            $lastShift = $shifts->last();

            // notification.shift.locked

            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_LOCKED);

            $userIdHasGetNotification = [];
            // Loop over the shifts and set is_committed to true
            foreach ($shifts as $shift) {
                $shift->is_committed = true;
                $shift->save();

                foreach ($shift->users()->get() as $user) {
                    if (!in_array($user->id, $userIdHasGetNotification)) {
                        $userIdHasGetNotification[] = $user->id;
                        $notificationTitle = __('notification.shift.locked');
                        if (!empty($firstShift) && !empty($lastShift)) {
                            $broadcastMessage = [
                                'id' => random_int(1, 1000000),
                                'type' => 'success',
                                'message' => $notificationTitle
                            ];

                            $notificationDescription = [
                                1 => [
                                    'type' => 'string',
                                    /*'title' => 'Betrifft Zeitraum: ' .
                                        Carbon::parse($firstShift->event_start_day . ' ' . $firstShift->start)
                                            ->format('d.m.Y H:i') . ' - ' .
                                        Carbon::parse($lastShift->event_end_day . ' ' . $lastShift->end)
                                            ->format('d.m.Y H:i'),*/
                                    'title' => __(
                                        'notification.keyWords.concerns_time_period',
                                        [
                                            'start' =>
                                                Carbon::parse($firstShift->event_start_day . '
                                                 ' . $firstShift->start)
                                                ->format('d.m.Y H:i'),
                                            'end' =>
                                                Carbon::parse($lastShift->event_end_day . '
                                                ' . $lastShift->end)->format('d.m.Y H:i')
                                        ],
                                        $user->language
                                    ),
                                    'href' => null
                                ],
                            ];
                            $this->notificationService->setDescription($notificationDescription);
                            $this->notificationService->setBroadcastMessage($broadcastMessage);
                        }
                        $this->notificationService->setTitle($notificationTitle);
                        $this->notificationService->setNotificationTo($user);
                        $this->notificationService->createNotification();
                    }
                }
            }
        }
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
        $room = $event->room()->first();
        $project = $event->project()->first();
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room->name,
                'href' => route('rooms.show', $room->id)
            ],
            2 => [
                'type' => 'string',
                'title' =>  $event->event_type()->first()->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $project->name,
                'href' => route('projects.show.calendar', $project->id)
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
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT);
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
        $room = $event->room()->first();
        $project = $event->project()->first();
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room->name,
                'href' => route('rooms.show', $room->id)
            ],
            2 => [
                'type' => 'string',
                'title' =>  $event->event_type()->first()->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $project->name,
                'href' => route('projects.show.calendar', $project->id)
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
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }

    private function createConflictNotification($collision, Event $event): void
    {

        $room = $event->room()->first();
        $project = $event->project()->first();

        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setEventId($collision['event']->id);
        $this->notificationService->setProjectId($collision['event']->project_id);
        $this->notificationService->setRoomId($collision['event']->room_id);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_CONFLICT);


        if (!empty($collision['created_by'])) {
            $user = User::find($collision['created_by']);
            // notification.event.conflict
            $notificationTitle = __('notification.event.conflict', [], $user->language);
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
                        $user->language
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
                    'title' =>  $event->event_type()->first()->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route('projects.show.calendar', $project->id) : null
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
            $this->notificationService->setNotificationTo($user);
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

    private function createRequestNotification($request, Event $event): void
    {
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_ROOM_REQUEST;
        $this->notificationData->event = $event;
        $this->notificationData->accepted = false;
        $room = Room::find($request->roomId);
        $admins = $room->users()->wherePivot('is_admin', true)->get();

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setEventId($event->id);
        $this->notificationService->setRoomId($room->id);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_REQUEST);

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
                        'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                        'href' => null
                    ],
                    3 => [
                        'type' => 'link',
                        'title' => $event->project()->first()->name ?? '',
                        'href' => $event->project()->first() ?
                            route('projects.show.calendar', $event->project()->first()->id) :
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
                    'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $event->project()->first()->name ?? '',
                    'href' => $event->project()->first() ?
                        route('projects.show.calendar', $event->project()->first()->id) :
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
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }
    }

    /**
     * @throws AuthorizationException
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function updateEvent(EventUpdateRequest $request, Event $event, ProjectController $projectController): CalendarEventResource
    {
        $this->authorize('update', $event);

        if (!$request->noNotifications) {
            $projectManagers = [];
            $this->notificationService->setNotificationKey($this->notificationKey);
            $room = $event->room()->first();
            $project = $event->project()->first();
            if (!empty($project)) {
                $projectManagers = $project->managerUsers()->get();
            }
            if (!empty($request->adminComment)) {
                $projectManagers = [];
                $this->notificationService->setNotificationKey($this->notificationKey);
                $project = $event->project()->first();
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
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_ANSWER);

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
                            'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                            'href' => null
                        ],
                        3 => [
                            'type' => 'link',
                            'title' => $project ? $project->name : '',
                            'href' => $project ? route('projects.show.calendar', $project->id) : null
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
                    $this->notificationService->setNotificationKey($this->notificationKey);
                    $this->notificationService->setNotificationTo($projectManager);
                    $this->notificationService->createNotification();
                }
                $notificationTitle = __('notification.event.admin_message', [], $event->creator()->first()->language);
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
                        'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                        'href' => null
                    ],
                    3 => [
                        'type' => 'link',
                        'title' => $project ? $project->name : '',
                        'href' => $project ? route('projects.show.calendar', $project->id) : null
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
                $this->notificationService->setNotificationKey($this->notificationKey);
                $this->notificationService->setNotificationTo($event->creator);
                $this->notificationService->createNotification();
            }
        }

        if ($request->roomChange) {
            $room = Room::find($event->room_id);
            $project = Project::find($event->project_id);


            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_CHANGED);
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
                        'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                        'href' => null
                    ],
                    3 => [
                        'type' => 'link',
                        'title' => $project ? $project->name : '',
                        'href' => $project ? route('projects.show.calendar', $project->id) : null
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
            $notificationTitle = __('notification.event.room_change_confirmed', [], $event->creator()->first()->language);
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
                    'href' => $project ? route('projects.show.calendar', $project->id) : null
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
        $oldIsLoud = $event->is_loud;
        $oldAudience = $event->audience;

        $event->update($request->data());

        if ($request->get('projectName')) {
            $project = Project::create(['name' => $request->get('projectName')]);
            $project->users()->save(Auth::user(), ['access_budget' => true]);
            $projectController->generateBasicBudgetValues($project);
            $event->project()->associate($project);
            $event->save();
        }

        if (!empty($event->project_id)) {
            $eventProject = $event->project()->first();
            $projectHistory = new NewHistoryService(Project::class);
            $projectHistory->createHistory($eventProject->id, 'Ablaufplan geändert');
        }

        $newEventDescription = $event->description;
        $newEventRoom = $event->room_id;
        $newEventProject = $event->project_id;
        $newEventName = $event->eventName;
        $newEventType = $event->event_type_id;
        $newEventStartDate = $event->start_time;
        $newEventEndDate = $event->end_time;
        $newIsLoud = $event->is_loud;
        $newAudience = $event->audience;

        $this->checkShortDescriptionChanges($event->id, $oldEventDescription, $newEventDescription);
        $this->checkRoomChanges($event->id, $oldEventRoom, $newEventRoom);
        $this->checkProjectChanges($event->id, $oldEventProject, $newEventProject);
        $this->checkEventNameChanges($event->id, $oldEventName, $newEventName);
        $this->checkEventTypeChanges($event->id, $oldEventType, $newEventType);
        $this->checkDateChanges($event->id, $oldEventStartDate, $newEventStartDate, $oldEventEndDate, $newEventEndDate);
        $this->checkEventOptionChanges($event->id, $oldIsLoud, $newIsLoud, $oldAudience, $newAudience);

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
            }
        }

        return new CalendarEventResource($event);
    }

    private function createEventScheduleNotification(Event $event): void
    {
        $schedule = new SchedulingController();
        if (!empty($event->project)) {
            foreach ($event->project->users->all() as $eventUser) {
                $schedule->create($eventUser->id, 'EVENT_CHANGES', 'EVENT', $event->id);
            }
        } else {
            $schedule->create($event->creator->id, 'EVENT_CHANGES', 'EVENT', $event->id);
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

        $this->notificationService->setNotificationKey($this->notificationKey);
        $room = Room::find($event->room_id);
        $admins = collect();
        if (!empty($room)) {
            $admins = $room->users()->wherePivot('is_admin', true)->get();
        }

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_REQUEST);
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
                            route('projects.show.calendar', $event->project()->first()->id) :
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
                        route('projects.show.calendar', $event->project()->first()->id) :
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

        $this->history->createHistory($event->id, 'Raum bestätigt');

        $event->save();
        $room = Room::find($event->room_id);
        $project = Project::find($event->project_id);
        $projectManagers = collect();
        if (!empty($project)) {
            $projectManagers = $project->managerUsers()->get();
        }
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST);

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
                    'href' => $project ? route('projects.show.calendar', $project->id) : null
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
                'href' => $project ? route('projects.show.calendar', $project->id) : null
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
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_ANSWER);
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
                        'href' => $project ? route('projects.show.calendar', $project->id) : null
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
                $this->notificationService->setNotificationKey($this->notificationKey);
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
                    'href' => $project ? route('projects.show.calendar', $project->id) : null
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
            $this->notificationService->setNotificationKey($this->notificationKey);
            $this->notificationService->setNotificationTo($event->creator);
            $this->notificationService->createNotification();
        }

        $this->notificationKey = Str::random(15);
        $this->history->createHistory($event->id, 'Raum abgelehnt');
        $room = Room::find($roomId);
        $project = Project::find($event->project_id);

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST);

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
                    'href' => $project ? route('projects.show.calendar', $project->id) : null
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
            $this->notificationService->setNotificationKey($this->notificationKey);
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
                'href' => $project ? route('projects.show.calendar', $project->id) : null
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
        $this->notificationService->setNotificationKey($this->notificationKey);
        $this->notificationService->setNotificationTo($event->creator);
        $this->notificationService->createNotification();
    }

    public function getCollisionCount(Request $request): int
    {
        $start = Carbon::parse($request->query('start'))->setTimezone(config('app.timezone'));
        $end = Carbon::parse($request->query('end'))->setTimezone(config('app.timezone'));

        return Event::query()
            ->whereOccursBetween($start, $end, true)
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
            ])
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroyShifts(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->shifts()->delete();
        $event->timeline()->delete();

        return redirect()->back();
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        if (!empty($event->project_id)) {
            $eventProject = $event->project()->first();
            $projectHistory = new NewHistoryService(Project::class);
            $projectHistory->createHistory($eventProject->id, 'Ablaufplan gelöscht');
        }

        $room = $event->room()->first();
        $project = $event->project()->first();
        $projectManagers = [];


        if (!empty($project)) {
            $projectManagers = $project->managerUsers()->get();
        }

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_ANSWER);

        foreach ($projectManagers as $projectManager) {
            if ($projectManager->id === $event->creator) {
                continue;
            }
            $notificationTitle = __('notification.event.deleted', [], $projectManager->language);
            $broadcastMessage = [
                'id' => random_int(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'link',
                    'title' => $room->name ?? '',
                    'href' => $room ? route('rooms.show', $room->id) : null
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $project->name ?? '',
                    'href' => $project ? route('projects.show.calendar', $project->id) : null
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
            $this->notificationService->setNotificationTo($projectManager);
            $this->notificationService->createNotification();
        }
        $notificationTitle = __('notification.event.deleted', [], $event->creator()->first()->language);
        $broadcastMessage = [
            'id' => random_int(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'link',
                'title' => $room->name ?? '',
                'href' => $room ? route('rooms.show', $room->id) : null
            ],
            2 => [
                'type' => 'string',
                'title' => $event->event_type()->first()->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $project->name ?? '',
                'href' => $project ? route('projects.show.calendar', $project->id) : null
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
        $this->notificationService->setNotificationTo($event->creator);
        $this->notificationService->createNotification();

        $event->subEvents()->delete();

        broadcast(new OccupancyUpdated())->toOthers();

        $this->notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

        $event->delete();

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function destroyByNotification(Event $event, Request $request): void
    {
        $this->authorize('delete', $event);

        if (!empty($event->project_id)) {
            $eventProject = $event->project()->first();
            $projectHistory = new NewHistoryService('Artwork\Modules\Project\Models\Project');
            $projectHistory->createHistory($eventProject->id, 'Ablaufplan gelöscht');
        }

        $room = Room::find($event->declined_room_id);
        $project = $event->project()->first();
        $projectManagers = [];

        if (!empty($project)) {
            $projectManagers = $project->managerUsers()->get();
        }

        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_ANSWER);

        foreach ($projectManagers as $projectManager) {
            if ($projectManager->id === $event->creator) {
                continue;
            }
            $notificationTitle = __('notification.event.deleted', [], $projectManager->language);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
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
                    'href' => $project ? route('projects.show.calendar', $project->id) : null
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
            $this->notificationService->setNotificationTo($projectManager);
            $this->notificationService->createNotification();
        }
        $notificationTitle = __('notification.event.deleted', [], $event->creator()->first()->language);
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
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
                'href' => $project ? route('projects.show.calendar', $project->id) : null
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
        $this->notificationService->setNotificationTo($event->creator);
        $this->notificationService->createNotification();

        if (!empty($request->notificationKey)) {
            $notifications = DatabaseNotification::query()
                ->whereJsonContains("data->notificationKey", $request->notificationKey)
                ->get();

            foreach ($notifications as $notification) {
                $notification->delete();
            }
        }

        if (!empty($event)) {
            $event->subEvents()->delete();

            broadcast(new OccupancyUpdated())->toOthers();
            $event->delete();
        }
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

        broadcast(new OccupancyUpdated())->toOthers();

        return Redirect::route('events.trashed')->with('success', 'Event deleted');
    }

    public function restore(int $id): RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($id);
        $event->subEvents()->restore();
        $event->restore();
        broadcast(new OccupancyUpdated())->toOthers();

        return Redirect::route('events.trashed')->with('success', 'Event restored');
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
            $this->history->createHistory($eventId, 'Datum/Uhrzeit geändert');
        }
    }

    private function checkEventTypeChanges($eventId, $oldType, $newType): void
    {
        if ($oldType !== $newType) {
            $this->history->createHistory($eventId, 'Termintyp geändert');
        }
    }

    private function checkEventNameChanges($eventId, $oldName, $newName): void
    {
        if ($oldName === null && $newName !== null) {
            $this->history->createHistory($eventId, 'Terminname hinzugefügt');
        }

        if ($oldName !== $newName && $newName !== null && $oldName !== null) {
            $this->history->createHistory($eventId, 'Terminname geändert');
        }

        if ($oldName !== null && $newName === null) {
            $this->history->createHistory($eventId, 'Terminname gelöscht');
        }
    }

    private function checkProjectChanges($eventId, $oldProject, $newProject): void
    {
        if ($newProject !== null && $oldProject === null) {
            $this->history->createHistory($eventId, 'Projektzuordnung hinzugefügt');
        }

        if ($oldProject !== null && $newProject === null) {
            $this->history->createHistory($eventId, 'Projektzuordnung gelöscht');
        }

        if ($newProject !== null && $oldProject !== null && $newProject !== $oldProject) {
            $this->history->createHistory($eventId, 'Projektzuordnung geändert');
        }
    }

    private function checkRoomChanges($eventId, $oldRoom, $newRoom): void
    {
        if ($oldRoom !== $newRoom) {
            $this->history->createHistory($eventId, 'Raum geändert');

            $this->notificationService->deleteUpsertRoomRequestNotificationByEventId($eventId);
        }
    }

    private function checkShortDescriptionChanges(int $eventId, $oldDescription, $newDescription): void
    {
        if ($newDescription === null && $oldDescription !== null) {
            $this->history->createHistory($eventId, 'Terminnotiz gelöscht');
        }
        if ($oldDescription === null && $newDescription !== null) {
            $this->history->createHistory($eventId, 'Terminnotiz hinzugefügt');
        }
        if ($oldDescription !== $newDescription && $oldDescription !== null && $newDescription !== null) {
            $this->history->createHistory($eventId, 'Terminnotiz geändert');
        }
    }

    private function checkEventOptionChanges(int $eventId, $isLoudOld, $isLoudNew, $audienceOld, $audienceNew): void
    {
        if ($isLoudOld !== $isLoudNew || $audienceOld !== $audienceNew) {
            $this->history->createHistory($eventId, 'Termineigenschaft geändert');
        }
    }

    public function deleteMultiEdit(Request $request): void
    {
        $eventIds = $request->events;
        foreach ($eventIds as $eventId) {
            $event = Event::find($eventId);
            $event->delete();
        }
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function updateMultiEdit(Request $request): void
    {
        $eventIds = $request->events;
        foreach ($eventIds as $eventId) {
            $event = Event::find($eventId);
            if ($request->newRoomId !== null) {
                $event->room_id = $request->newRoomId;
            }
            if ($request->date === null) {
                if ($request->value !== 0) {
                    $endDate = Carbon::parse($event->end_time);
                    $startDate = Carbon::parse($event->start_time);

                    // plus
                    if ($request->calculationType === 1) {
                        // stunden
                        if ($request->type === 1) {
                            $event->start_time = $startDate->addHours($request->value);
                            $event->end_time = $endDate->addHours($request->value);
                        }
                        // Tage
                        if ($request->type === 2) {
                            $event->start_time = $startDate->addDays($request->value);
                            $event->end_time = $endDate->addDays($request->value);
                        }
                        // Wochen
                        if ($request->type === 3) {
                            $event->start_time = $startDate->addWeeks($request->value);
                            $event->end_time = $endDate->addWeeks($request->value);
                        }
                        // Monate
                        if ($request->type === 4) {
                            $event->start_time = $startDate->addMonths($request->value);
                            $event->end_time = $endDate->addMonths($request->value);
                        }
                        // Jahre
                        if ($request->type === 5) {
                            $event->start_time = $startDate->addYears($request->value);
                            $event->end_time = $endDate->addYears($request->value);
                        }
                    }

                    // plus
                    if ($request->calculationType === 2) {
                        // stunden
                        if ($request->type === 1) {
                            $event->start_time = $startDate->subHours($request->value);
                            $event->end_time = $endDate->subHours($request->value);
                        }
                        // Tage
                        if ($request->type === 2) {
                            $event->start_time = $startDate->subDays($request->value);
                            $event->end_time = $endDate->subDays($request->value);
                        }
                        // Wochen
                        if ($request->type === 3) {
                            $event->start_time = $startDate->subWeeks($request->value);
                            $event->end_time = $endDate->subWeeks($request->value);
                        }
                        // Monate
                        if ($request->type === 4) {
                            $event->start_time = $startDate->subMonths($request->value);
                            $event->end_time = $endDate->subMonths($request->value);
                        }
                        // Jahre
                        if ($request->type === 5) {
                            $event->start_time = $startDate->subYears($request->value);
                            $event->end_time = $endDate->subYears($request->value);
                        }
                    }
                }
            } else {
                $endTime = Carbon::parse($event->end_time)->format('H:i:s');
                $startTime = Carbon::parse($event->start_time)->format('H:i:s');

                $date = Carbon::parse($request->date)->format('Y-m-d');

                $event->start_time = $date . ' ' . $startTime;
                $event->end_time = $date . ' ' . $endTime;
            }

            $event->save();
        }
    }
}
