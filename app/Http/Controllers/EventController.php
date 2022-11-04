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
use App\Models\Task;
use App\Support\Services\CollisionService;
use App\Support\Services\HistoryService;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class EventController extends Controller
{

    protected ?NotificationController $notificationController = null;
    protected ?\stdClass $notificationData = null;
    protected ?CollisionService $collisionService = null;

    public function __construct()
    {
        $this->collisionService = new CollisionService();
        $this->notificationController = new NotificationController();
        $this->notificationData = new \stdClass();
        $this->notificationData->event = new \stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_EVENT;
    }

    public function viewEventIndex(Request $request): Response
    {
        return inertia('Events/EventManagement', [
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve()
        ]);
    }

    public function showDashboardPage(Request $request): Response
    {
        $projects = Project::query()->with(['adminUsers', 'managerUsers'])->get();

        $tasks = Task::query()
            ->whereHas('checklist', fn (Builder $checklistBuilder) => $checklistBuilder
                ->where('user_id', Auth::id())
            )
            ->orWhereHas('checklistDepartments', fn (Builder $departmentBuilder) => $departmentBuilder
                ->whereHas('users', fn (Builder $userBuilder) => $userBuilder
                    ->where('users.id', Auth::id()))
            )
            ->get();

        return inertia('Dashboard', [
            'projects' => ProjectIndexAdminResource::collection($projects)->resolve(),
            'tasks' => TaskIndexResource::collection($tasks)->resolve(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
        ]);
    }

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

    public function storeEvent(EventStoreRequest $request, HistoryService $historyService): CalendarEventResource
    {
        $this->authorize('create', Event::class);


        // check if any event in collision. If any event has collision send notification to user
        if($this->collisionService->getCollisionCount($request)->count() > 0){
            $this->notificationData->type = NotificationConstEnum::NOTIFICATION_CONFLICT;
            $this->notificationData->title = 'Terminkonflikt';
            $this->notificationData->conflict = new \stdClass();
            $this->notificationData->conflict = $this->collisionService->getConflictEvents($request);
            $this->notificationData->created_by = Auth::id();
            $this->notificationController->create(Auth::user(), $this->notificationData);
            // reset notification type to event notification
            $this->notificationData->type = NotificationConstEnum::NOTIFICATION_EVENT;
        }

        /** @var Event $event */
        $event = Event::create($request->data());



        if ($request->get('projectName')) {
            $project = Project::create(['name' => $request->get('projectName')]);
            $project->users()->save(Auth::user(), ['is_admin' => true]);
            $event->project()->associate($project);
            $event->save();
            $historyService->projectUpdated($project);
        }

        // create and send notification data
        $this->notificationData->title = 'Termin hinzugefügt';
        $this->notificationData->event->id = $event->id;
        $this->notificationData->event->title = $event->eventName;
        $this->notificationData->created_by = Auth::id();
        $this->notificationController->create($event->project->users->all(), $this->notificationData);

        broadcast(new OccupancyUpdated())->toOthers();

        return new CalendarEventResource($event);
    }

    /**
     * @param EventUpdateRequest $request
     * @param Event $event
     * @param HistoryService $historyService
     * @return CalendarEventResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateEvent(EventUpdateRequest $request, Event $event, HistoryService $historyService): CalendarEventResource
    {
        $this->authorize('update', $event);

        $event->update($request->data());

        if ($request->get('projectName')) {
            $project = Project::create(['name' => $request->get('projectName')]);
            $project->users()->save(Auth::user(), ['is_admin' => true]);
            $event->project()->associate($project);
            $event->save();
            $historyService->projectUpdated($project);
        }

        // create and send notification data
        $this->notificationData->title = 'Termin geändert';
        $this->notificationData->event->id = $event->id;
        $this->notificationData->event->title = $event->eventName;
        $this->notificationData->created_by = Auth::id();
        $this->notificationController->create($event->project->users->all(), $this->notificationData);

        return new CalendarEventResource($event);
    }

    public function acceptEvent(EventAcceptionRequest $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        $event->occupancy_option = false;
        if (!$request->get('accepted')) {
            $this->notificationData->title = 'Raumanfrage abgelehnt';
            $event->room_id = null;
        } else {
            $this->notificationData->title = 'Raumanfrage bestätigt';
        }
        $event->save();

        $this->notificationData->event->id = $event->id;
        $this->notificationData->event->title = $event->eventName;
        $this->notificationData->created_by = Auth::id();
        $this->notificationController->create($event->creator, $this->notificationData);

        return Redirect::back();
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


        $events = Event::query()
            // eager loading
            ->withCollisionCount()
            ->with('room')
            // filter for different pages
            ->whereOccursBetween(Carbon::parse($request->get('start')), Carbon::parse($request->get('end')))
            ->when($projectId, fn (EventBuilder $builder) => $builder->where('project_id', $projectId))
            ->when($roomId, fn (EventBuilder $builder) => $builder->where('room_id', $roomId))
            //war in alter Version, relevant für dich Paul ?
            ->applyFilter(json_decode($request->input('calendarFilters'), true))
            // user applied filters
            ->unless(empty($roomIds) && empty($areaIds) && empty($roomAttributeIds), fn (EventBuilder $builder) => $builder
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
    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        broadcast(new OccupancyUpdated())->toOthers();
        $event->delete();

        // create and send notification to event owner
        $this->notificationData->title = 'Event ' . $event->eventName . ' wurde gelöscht';
        $this->notificationData->event->id = $event->id;
        $this->notificationData->event->title = $event->eventName;
        $this->notificationData->created_by = Auth::id();
        $this->notificationController->create($event->creator()->get(), $this->notificationData);

        return new JsonResponse(['success' => 'Event moved to trash']);
    }

    public function forceDelete(int $id): \Illuminate\Http\RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($id);
        $this->authorize('delete', $event);
        $event->forceDelete();
        broadcast(new OccupancyUpdated())->toOthers();

        return Redirect::route('events.trashed')->with('success', 'Event deleted');
    }

    public function restore(int $id): \Illuminate\Http\RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($id);
        $event->restore();
        broadcast(new OccupancyUpdated())->toOthers();

        return Redirect::route('events.trashed')->with('success', 'Event restored');
    }
}
