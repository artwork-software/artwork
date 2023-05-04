<?php

namespace App\Http\Controllers;

use App\Builders\EventBuilder;
use App\Enums\NotificationConstEnum;
use App\Events\OccupancyUpdated;
use App\Http\Requests\EventAcceptionRequest;
use App\Http\Requests\EventIndexRequest;
use App\Http\Requests\EventStoreRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Resources\CalendarEventCollectionResource;
use App\Http\Resources\CalendarEventResource;
use App\Http\Resources\EventShowResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectIndexAdminResource;
use App\Http\Resources\TaskIndexResource;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\Scheduling;
use App\Models\SeriesEvents;
use App\Models\SubEvents;
use App\Models\Task;
use App\Models\User;
use App\Support\Services\CollisionService;
use App\Support\Services\HistoryService;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class EventController extends Controller
{

    protected ?NotificationService $notificationService = null;
    protected ?\stdClass $notificationData = null;
    protected ?CollisionService $collisionService = null;
    protected ?NewHistoryService $history = null;

    public function __construct()
    {
        $this->collisionService = new CollisionService();
        $this->notificationService = new NotificationService();
        $this->notificationData = new \stdClass();
        $this->notificationData->event = new \stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_EVENT_CHANGED;
        $this->history = new NewHistoryService('App\Models\Event');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function viewEventIndex(Request $request): Response
    {

        $calendar = new CalendarController();
        $showCalendar = $calendar->createCalendarData();

        $eventsAtAGlance = [];

        if(\request('startDate') && \request('endDate')){
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        }else{
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }

        if(\request('atAGlance') === 'true') {
            $eventsAtAGlance = CalendarEventResource::collection(Event::query()
                ->whereBetween('start_time', [$startDate, $endDate])
                ->whereBetween('end_time', [$startDate, $endDate])
                ->with(['room', 'project', 'creator'])
                ->orderBy('start_time', 'ASC')->get())->collection->groupBy('room.id');
        }

        return inertia('Events/EventManagement', [
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'calendar' => $showCalendar['roomsWithEvents'],
            'days' => $showCalendar['days'],
            'dateValue'=> $showCalendar['dateValue'],
            'calendarType' => $showCalendar['calendarType'],
            'selectedDate' => $showCalendar['selectedDate'],
            'eventsWithoutRoom' => $showCalendar['eventsWithoutRoom'],
            'eventsAtAGlance' => $eventsAtAGlance,
            'rooms' => $calendar->filterRooms(),
            'events' => new CalendarEventCollectionResource($calendar->getEventsOfDay()),
            'filterOptions' => $showCalendar["filterOptions"],
            'personalFilters' => $showCalendar['personalFilters']
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function showDashboardPage(Request $request): Response
    {
        $calendarController = new CalendarController();
        $showCalendar = $calendarController->createCalendarData('dashboard');

        $projects = Project::query()->with( ['managerUsers'])->get();

        $tasks = Task::query()
            ->whereHas('checklist', fn (Builder $checklistBuilder) => $checklistBuilder
                ->where('user_id', Auth::id())
            )
            ->orWhereHas('task_users', fn (Builder $userBuilder) => $userBuilder
                ->where('user_id', Auth::id())
            )
            ->get();

        $eventsAtAGlance = [];

        if(\request('startDate') && \request('endDate')){
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        }else{
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }

        if(request('atAGlance') === 'true') {
            $eventsAtAGlance = $calendarController->getEventsAtAGlance($startDate, $endDate);
        }

        $rooms = $calendarController->filterRooms();

        return inertia('Dashboard', [
            'projects' => ProjectIndexAdminResource::collection($projects)->resolve(),
            'tasks' => TaskIndexResource::collection($tasks)->resolve(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'calendar' => $showCalendar['roomsWithEvents'],
            'days' => $showCalendar['days'],
            'dateValue'=> $showCalendar['dateValue'],
            'calendarType' => $showCalendar['calendarType'],
            'selectedDate' => $showCalendar['selectedDate'],
            'rooms' => $rooms,
            'eventsWithoutRoom' => $showCalendar['eventsWithoutRoom'],
            'eventsAtAGlance' => $eventsAtAGlance,
            'events' => new CalendarEventCollectionResource($calendarController->getEventsOfDay()),
            'filterOptions' => $showCalendar["filterOptions"],
            'personalFilters' => $showCalendar['personalFilters']
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function viewRequestIndex(Request $request): Response
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

    /**
     * @param EventStoreRequest $request
     * @return CalendarEventResource
     */
    public function storeEvent(EventStoreRequest $request): CalendarEventResource
    {
        // Adjoining Room / Event check
        $this->adjoiningRoomsCheck($request);
        $firstEvent = Event::create($request->data());
        if ($request->get('projectName')) {
            $this->associateProject($request, $firstEvent);
        }

        if($request->is_series){
            $series = SeriesEvents::create([
                'frequency_id' => $request->seriesFrequency,
                'end_date' => $request->seriesEndDate
            ]);
            $firstEvent->update(['is_series' => true, 'series_id' => $series->id]);
            $endSeriesDate = Carbon::parse($request->seriesEndDate)->addDay();
            $startDate = Carbon::parse($request->start)->setTimezone(config('app.timezone'));
            $endDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));
            $whileEndDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));
            if($request->seriesFrequency === 1){
                while ($whileEndDate->addDay() < $endSeriesDate) {
                    $startDate = $startDate->addDay();
                    $endDate = $endDate->addDay();
                    $this->createSeriesEvent($startDate, $endDate, $request, $series);
                }
            }
            if($request->seriesFrequency === 2){
                while ($whileEndDate->addWeek() < $endSeriesDate) {
                    $startDate = $startDate->addWeek();
                    $endDate = $endDate->addWeek();
                    $this->createSeriesEvent($startDate, $endDate, $request, $series);
                }
            }
            if($request->seriesFrequency === 3){
                while ($whileEndDate->addWeeks(2) < $endSeriesDate) {
                    $startDate = $startDate->addWeeks(2);
                    $endDate = $endDate->addWeeks(2);
                    $this->createSeriesEvent($startDate, $endDate, $request, $series);
                }
            }
            if($request->seriesFrequency === 4){
                while ($whileEndDate->addMonth() < $endSeriesDate) {
                    $startDate = $startDate->addMonth();
                    $endDate = $endDate->addMonth();
                    $this->createSeriesEvent($startDate, $endDate, $request, $series);
                }
            }
        }

        if(!empty($firstEvent->project()->get())){
            $eventProject = $firstEvent->project()->first();
            if($eventProject){
                $projectHistory = new NewHistoryService('App\Models\Project');
                $projectHistory->createHistory($eventProject->id, 'Ablaufplan hinzugefügt');
            }
        }

        if($request->isOption){
            $this->createRequestNotification($request, $firstEvent);
        }

        broadcast(new OccupancyUpdated())->toOthers();

        return new CalendarEventResource($firstEvent);
    }

    private function createSeriesEvent($startDate,$endDate, $request, $series){
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
            'room_id' => $request->roomId,
            'user_id' => Auth::id(),
            'project_id' => $request->projectId,
            'is_series' => true,
            'series_id' => $series->id,
        ]);
    }

    /**
     * @param EventStoreRequest $request
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function adjoiningRoomsCheck(EventStoreRequest $request) {
        $joiningEvents = $this->collisionService->adjoiningCollision($request);
        foreach ($joiningEvents as $joiningEvent){
            foreach ($joiningEvent as $conflict){
                $user = User::find($conflict->user_id);
                if($user->id === Auth::id()){
                    continue;
                }
                if($request->audience){
                    $this->createAdjoiningAudienceNotification($conflict, $user);
                }
                if($request->isLoud){
                    $this->createAdjoiningLoudNotification($conflict, $user);
                }
            }
        }
        $this->authorize('create', Event::class);

        if($this->collisionService->getCollision($request)->count() > 0){
            $collisions = $this->collisionService->getConflictEvents($request);
            if(!empty($collisions)){
                foreach ($collisions as $collision){
                    $this->createConflictNotification($collision);
                }
            }
        }
    }

    /**
     * @param $conflict
     * @param User $user
     * @return void
     */
    private function createAdjoiningAudienceNotification($conflict, User $user) {
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT;
        $this->notificationData->title = 'Termin mit Publikum im Nebenraum';
        $this->notificationData->conflict = $conflict;
        $this->notificationData->created_by = Auth::user();
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $this->notificationData->title
        ];
        $this->notificationService->create($user, $this->notificationData, $broadcastMessage);
    }

    /**
     * @param $conflict
     * @param User $user
     * @return void
     */
    private function createAdjoiningLoudNotification($conflict, User $user) {
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_LOUD_ADJOINING_EVENT;
        $this->notificationData->title = 'Lauter Termin im Nebenraum';
        $this->notificationData->conflict = $conflict;
        $this->notificationData->created_by = Auth::user();
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $this->notificationData->title
        ];
        $this->notificationService->create($user, $this->notificationData, $broadcastMessage);
    }

    /**
     * @param $collision
     * @return void
     */
    private function createConflictNotification($collision) {
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_CONFLICT;
        $this->notificationData->title = 'Terminkonflikt';
        $this->notificationData->conflict = $collision;
        $this->notificationData->created_by = Auth::user();
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $this->notificationData->title
        ];
        if(!empty($collision['created_by'])){
            $this->notificationService->create($collision['created_by'], $this->notificationData, $broadcastMessage);
        }
    }

    /**
     * @param $request
     * @param $event
     * @return void
     */
    private function associateProject($request, $event) {
        $project = Project::create(['name' => $request->get('projectName')]);
        $project->users()->save(Auth::user(), ['access_budget' => true]);
        $projectController = new ProjectController();
        $projectController->generateBasicBudgetValues($project);
        $event->project()->associate($project);
        $event->save();
    }

    /**
     * @param $request
     * @param $event
     * @return void
     */
    private function createRequestNotification($request, $event) {
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_ROOM_REQUEST;
        $this->notificationData->title = 'Neue Raumanfrage';
        $this->notificationData->event = $event;
        $this->notificationData->accepted = false;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $this->notificationData->title
        ];
        $this->notificationData->created_by = Auth::user();
        $room = Room::find($request->roomId);
        $admins = $room->users()->wherePivot('is_admin', true)->get();
        if(!empty($admins)){
            foreach ($admins as $admin){
                $this->notificationService->create($admin, $this->notificationData, $broadcastMessage);
            }
        } else {
            $user = User::find($room->user_id);
            $this->notificationService->create($user, $this->notificationData, $broadcastMessage);
        }
    }

    /**
     * @param EventUpdateRequest $request
     * @param Event $event
     * @param HistoryService $historyService
     * @return CalendarEventResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateEvent(EventUpdateRequest $request, Event $event): CalendarEventResource
    {
        if($request->accept || $request->optionAccept){
            $event->update(['occupancy_option' => false]);

            if(!empty($request->adminComment)){
                $event->comments()->create([
                    'user_id' => Auth::id(),
                    'comment' => $request->adminComment,
                    'is_admin_comment' => true
                ]);
            }

            if($request->accept){
                $event->update(['accepted' => true]);
            }

            if($request->optionAccept){
                $event->update(['accepted' => true, 'option_string' => $request->optionString]);
            }

        }


        DatabaseNotification::query()
            ->whereJsonContains("data->type", "NOTIFICATION_UPSERT_ROOM_REQUEST")
            ->orWhereJsonContains("data->type", "ROOM_REQUEST")
            ->whereJsonContains("data->event->id", $event->id)
            ->delete();

        if($request->roomChange){
            $this->notificationData->title = 'Raumanfrage mit Raumänderung bestätigt';
            $this->notificationData->type = NotificationConstEnum::NOTIFICATION_ROOM_REQUEST;
            $this->notificationData->event = $event;
            $this->notificationData->accepted = true;
            $this->notificationData->created_by = Auth::user();
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $this->notificationData->title
            ];
            $this->notificationService->create($event->creator, $this->notificationData, $broadcastMessage);
        }

        $this->authorize('update', $event);

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
            $projectController = new ProjectController();
            $projectController->generateBasicBudgetValues($project);
            $event->project()->associate($project);
            $event->save();
        }

        if(!empty($event->project_id)){
            $eventProject = $event->project()->first();
            $projectHistory = new NewHistoryService('App\Models\Project');
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

        if($request->allSeriesEvents){
            if($event->is_series){
                $seriesEvents = Event::where('series_id', $event->series_id)->get();
                foreach ($seriesEvents as $seriesEvent){
                    // Guard
                    if($seriesEvent->id === $event->id){
                        continue;
                    }

                    $startDay = Carbon::create($seriesEvent->start_time)->addDays($diffStartDays)->format('Y-m-d');
                    $endDay = Carbon::create($seriesEvent->end_time)->addDays($diffEndDays)->format('Y-m-d');

                    $startTime = Carbon::create($seriesEvent->start_time)->addMinutes($diffStartMinutes)->format('H:i:s');
                    $endTime = Carbon::create($seriesEvent->end_time)->addMinutes($diffEndMinutes)->format('H:i:s');

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

    /**
     * @param Event $event
     * @return void
     */
    private function createEventScheduleNotification(Event $event): void
    {
        $schedule = new SchedulingController();
        if(!empty($event->project)){
            foreach ($event->project->users->all() as $eventUser){
                $schedule->create($eventUser->id, 'EVENT_CHANGES', 'EVENT', $event->id);
            }
        } else {
            $schedule->create($event->creator->id, 'EVENT_CHANGES', 'EVENT', $event->id);
        }
    }

    /**
     * @param EventAcceptionRequest $request
     * @param Event $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function acceptEvent(EventAcceptionRequest $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        DatabaseNotification::query()
            ->whereJsonContains("data->type", "NOTIFICATION_UPSERT_ROOM_REQUEST")
            ->orWhereJsonContains("data->type", "ROOM_REQUEST")
            ->whereJsonContains("data->event->id", $event->id)
            ->delete();

        $event->occupancy_option = false;
        if (!$request->get('accepted')) {
            $this->notificationData->title = 'Raumanfrage abgelehnt';
            $this->history->createHistory($event->id, 'Raum abgelehnt');
            $this->notificationData->accepted = false;
            $event->declined_room_id = $event->room_id;
            $event->room_id = null;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $this->notificationData->title
            ];

        } else {
            $this->notificationData->title = 'Raumanfrage bestätigt';
            $this->history->createHistory($event->id, 'Raum bestätigt');
            $this->notificationData->accepted = true;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $this->notificationData->title
            ];
        }
        $event->save();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST;
        $this->notificationData->event = $event;
        $this->notificationData->created_by = User::where('id', Auth::id())->first();
        $this->notificationService->create($event->creator, $this->notificationData, $broadcastMessage);

        return Redirect::back();
    }


    public function declineEvent(Request $request, Event $event){
        $roomId = $event->room_id;
        $event->update(['accepted' => false, 'declined_room_id' => $roomId, 'room_id' => null]);
        if(!empty($request->comment)){
            $event->comments()->create([
                'user_id' => Auth::id(),
                'comment' => $request->comment,
                'is_admin_comment' => true
            ]);
        }
        $this->notificationData->title = 'Raumanfrage abgesagt';
        $this->history->createHistory($event->id, 'Raum abgelehnt');
        $this->notificationData->accepted = false;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $this->notificationData->title
        ];
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST;
        $this->notificationData->event = $event;
        $this->notificationData->created_by = User::where('id', Auth::id())->first();
        $this->notificationService->create($event->creator, $this->notificationData, $broadcastMessage);

    }

    /**
     * @param Request $request
     * @return int
     */
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

    /**
     * @param EventIndexRequest $request
     * @return CalendarEventCollectionResource[]
     */
    public function eventIndex(EventIndexRequest $request): array
    {
        $calendarFilters = json_decode($request->input('calendarFilters'));
        $projectId = $request->get('projectId');
        $roomId = $request->get('roomId');
        $roomIds = $calendarFilters->roomIds;
        $areaIds = $calendarFilters->areaIds;
        $eventTypeIds = $calendarFilters->eventTypeIds;
        $roomAttributeIds = $calendarFilters->roomAttributeIds;
        $roomCategoryIds = $calendarFilters->roomCategoryIds;
        $isLoud = $calendarFilters->isLoud;
        $isNotLoud = $calendarFilters->isNotLoud;
        $hasAudience = $calendarFilters->hasAudience;
        $hasNoAudience = $calendarFilters->hasNoAudience;
        $showAdjoiningRooms = $calendarFilters->showAdjoiningRooms;

        if($request->get('projectId')){
            $eventsWithoutRoom = Event::query()->whereNull('room_id')->where('project_id', $request->get('projectId'))->get();
        }else{
            $eventsWithoutRoom = Event::query()->whereNull('room_id')->where('user_id',Auth::id())->get();
        }

        Debugbar::info($roomAttributeIds);
        Debugbar::info($roomCategoryIds);

        $events = Event::query()
            // eager loading
            ->withCollisionCount()
            ->with(['room'])
            // filter for different pages
            ->whereOccursBetween(Carbon::parse($request->get('start')), Carbon::parse($request->get('end')))
            ->when($projectId, fn (EventBuilder $builder) => $builder->where('project_id', $projectId))
            ->when($roomId, fn (EventBuilder $builder) => $builder->where('room_id', $roomId))
            //war in alter Version, relevant für dich Paul ?
            ->applyFilter(json_decode($request->input('calendarFilters'), true))
            // user applied filters
            ->unless(empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds), fn (EventBuilder $builder) => $builder
                ->whereHas('room', fn (Builder $roomBuilder) => $roomBuilder
                    ->when($roomIds, fn (Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                    ->when($areaIds, fn (Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                    ->when($showAdjoiningRooms, fn(Builder $roomBuilder) => $roomBuilder->with('adjoining_rooms'))
                    ->when($roomAttributeIds, fn (Builder $roomBuilder) => $roomBuilder
                        ->whereHas('attributes', fn (Builder $roomAttributeBuilder) => $roomAttributeBuilder
                            ->whereIn('room_attributes.id', $roomAttributeIds)))
                    ->when($roomCategoryIds, fn (Builder $roomBuilder) => $roomBuilder
                        ->whereHas('categories', fn (Builder $roomCategoryBuilder) => $roomCategoryBuilder
                            ->whereIn('room_categories.id', $roomCategoryIds)))
                )
            )
            ->unless(empty($eventTypeIds), fn (EventBuilder $builder) => $builder->whereIn('event_type_id', $eventTypeIds))
            ->unless(is_null($isLoud), fn (EventBuilder $builder) => $builder->where('is_loud', $isLoud))
            ->unless(is_null($isNotLoud), fn (EventBuilder $builder) => $builder->where('is_loud',null))
            ->unless(is_null($hasAudience), fn (EventBuilder $builder) => $builder->where('audience', $hasAudience))
            ->unless(is_null($hasNoAudience), fn (EventBuilder $builder) => $builder->where('audience', null))
            ->get();

        return [
            'events' => new CalendarEventCollectionResource($events),
            'eventsWithoutRoom' => new CalendarEventCollectionResource($eventsWithoutRoom),
        ];
    }

    /**
     * @return Response|\Inertia\ResponseFactory
     */
    public function getTrashed(): Response|\Inertia\ResponseFactory
    {
        return inertia('Trash/Events', [
            'trashed_events' => Event::onlyTrashed()->get()->map(fn ($event) => [
                'id' => $event->id,
                'name' => $event->eventName,
                'project'=> $event->project,
                'event_type' => $event->event_type,
                'start' => $event->start_time->format('d.m.Y, H:i'),
                'end' => $event->end_time->format('d.m.Y, H:i'),
                'room_name' => $event->room?->label,
            ])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Event $event
     * @return JsonResponse
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        if(!empty($event->project_id)){
            $eventProject = $event->project()->first();
            $projectHistory = new NewHistoryService('App\Models\Project');
            $projectHistory->createHistory($eventProject->id, 'Ablaufplan gelöscht');
        }

        $event->subEvents()->delete();

        broadcast(new OccupancyUpdated())->toOthers();
        $event->delete();

        // create and send notification to event owner
        $this->notificationData->title = 'Termin abgesagt';
        $this->notificationData->event = $event;
        $this->notificationData->created_by = User::where('id', Auth::id())->first();
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $this->notificationData->title
        ];
        $this->notificationService->create($event->creator()->get(), $this->notificationData, $broadcastMessage);
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function forceDelete(int $id): \Illuminate\Http\RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($id);
        $this->authorize('delete', $event);
        $event->forceDelete();
        broadcast(new OccupancyUpdated())->toOthers();

        return Redirect::route('events.trashed')->with('success', 'Event deleted');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore(int $id): \Illuminate\Http\RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($id);
        $event->restore();
        broadcast(new OccupancyUpdated())->toOthers();

        return Redirect::route('events.trashed')->with('success', 'Event restored');
    }

    /**
     * @param $eventId
     * @param $oldEventStartDate
     * @param $newEventStartDate
     * @param $oldEventEndDate
     * @param $newEventEndDate
     * @return void
     */
    private function checkDateChanges($eventId, $oldEventStartDate, $newEventStartDate, $oldEventEndDate, $newEventEndDate): void
    {
        if(strtotime($oldEventStartDate) !== strtotime($newEventStartDate) || strtotime($oldEventEndDate) !== strtotime($newEventEndDate)){
            $this->history->createHistory($eventId, 'Datum/Uhrzeit geändert');
        }
    }

    /**
     * @param $eventId
     * @param $oldType
     * @param $newType
     * @return void
     */
    private function checkEventTypeChanges($eventId, $oldType, $newType): void
    {
        if($oldType !== $newType){
            $this->history->createHistory($eventId, 'Termintyp geändert');
        }
    }

    /**
     * @param $eventId
     * @param $oldName
     * @param $newName
     * @return void
     */
    private function checkEventNameChanges($eventId, $oldName, $newName): void
    {
        if($oldName === null && $newName !== null){
            $this->history->createHistory($eventId, 'Terminname hinzugefügt');
        }

        if($oldName !== $newName && $newName !== null && $oldName !== null){
            $this->history->createHistory($eventId, 'Terminname geändert');
        }

        if($oldName !== null && $newName === null){
            $this->history->createHistory($eventId, 'Terminname gelöscht');
        }
    }

    /**
     * @param $eventId
     * @param $oldProject
     * @param $newProject
     * @return void
     */
    private function checkProjectChanges($eventId, $oldProject, $newProject): void
    {
        if($newProject !== null && $oldProject === null){
            $this->history->createHistory($eventId, 'Projektzuordnung hinzugefügt');
        }

        if($oldProject !== null && $newProject === null ){
            $this->history->createHistory($eventId, 'Projektzuordnung gelöscht');
        }

        if($newProject !== null && $oldProject !== null && $newProject !== $oldProject ){
            $this->history->createHistory($eventId, 'Projektzuordnung geändert');
        }
    }

    /**
     * @param $eventId
     * @param $oldRoom
     * @param $newRoom
     * @return void
     */
    private function checkRoomChanges($eventId, $oldRoom, $newRoom): void
    {
        if($oldRoom !== $newRoom){
            $this->history->createHistory($eventId, 'Raum geändert');
        }
    }

    /**
     * @param int $eventId
     * @param $oldDescription
     * @param $newDescription
     * @return void
     */
    private function checkShortDescriptionChanges(int $eventId, $oldDescription, $newDescription): void
    {
        if($newDescription === null && $oldDescription !== null){
            $this->history->createHistory($eventId, 'Terminnotiz gelöscht');
        }
        if($oldDescription === null && $newDescription !== null){
            $this->history->createHistory($eventId, 'Terminnotiz hinzugefügt');
        }
        if($oldDescription !== $newDescription && $oldDescription !== null && $newDescription !== null){
            $this->history->createHistory($eventId, 'Terminnotiz geändert');
        }
    }

    /**
     * @param int $eventId
     * @param $isLoudOld
     * @param $isLoudNew
     * @param $audienceOld
     * @param $audienceNew
     * @return void
     */
    private function checkEventOptionChanges(int $eventId, $isLoudOld, $isLoudNew, $audienceOld, $audienceNew){
        if($isLoudOld !== $isLoudNew || $audienceOld !== $audienceNew){
            $this->history->createHistory($eventId, 'Termineigenschaft geändert');
        }
    }


    public function deleteMultiEdit(Request $request){
        $eventIds = $request->events;
        foreach ($eventIds as $eventId) {
            $event = Event::find($eventId);
            $event->delete();
        }
    }


    /**
     * @param Request $request
     * @return void
     */
    public function updateMultiEdit(Request $request){
        $eventIds = $request->events;

        foreach ($eventIds as $eventId){
            $event = Event::find($eventId);
            if($request->newRoomId !== null){
                $event->room_id = $request->newRoomId;
            }
            if($request->date === null){
                if($request->value !== 0){
                    $endDate = Carbon::parse($event->end_time);
                    $startDate = Carbon::parse($event->start_time);

                    // plus
                    if($request->calculationType === 1){
                        // stunden
                        if($request->type === 1){
                            $event->start_time = $startDate->addHours($request->value);
                            $event->end_time = $endDate->addHours($request->value);
                        }
                        // Tage
                        if($request->type === 2){
                            $event->start_time = $startDate->addDays($request->value);
                            $event->end_time = $endDate->addDays($request->value);
                        }
                        // Wochen
                        if($request->type === 3){
                            $event->start_time = $startDate->addWeeks($request->value);
                            $event->end_time = $endDate->addWeeks($request->value);
                        }
                        // Monate
                        if($request->type === 4){
                            $event->start_time = $startDate->addMonths($request->value);
                            $event->end_time = $endDate->addMonths($request->value);
                        }
                        // Jahre
                        if($request->type === 5){
                            $event->start_time = $startDate->addYears($request->value);
                            $event->end_time = $endDate->addYears($request->value);
                        }
                    }

                    // plus
                    if($request->calculationType === 2){
                        // stunden
                        if($request->type === 1){
                            $event->start_time = $startDate->subHours($request->value);
                            $event->end_time = $endDate->subHours($request->value);
                        }
                        // Tage
                        if($request->type === 2){
                            $event->start_time = $startDate->subDays($request->value);
                            $event->end_time = $endDate->subDays($request->value);
                        }
                        // Wochen
                        if($request->type === 3){
                            $event->start_time = $startDate->subWeeks($request->value);
                            $event->end_time = $endDate->subWeeks($request->value);
                        }
                        // Monate
                        if($request->type === 4){
                            $event->start_time = $startDate->subMonths($request->value);
                            $event->end_time = $endDate->subMonths($request->value);
                        }
                        // Jahre
                        if($request->type === 5){
                            $event->start_time = $startDate->subYears($request->value);
                            $event->end_time = $endDate->subYears($request->value);
                        }
                    }
                }
            } else {
                $endTime = Carbon::parse($event->end_time)->format('H:i:s');
                $startTime = Carbon::parse($event->start_time)->format('H:i:s');

                $date = Carbon::parse($request->date)->format('Y-m-d');

                $event->start_time = $date . ' '. $startTime;
                $event->end_time = $date . ' '. $endTime;
            }


            $event->save();
        }
    }

}
