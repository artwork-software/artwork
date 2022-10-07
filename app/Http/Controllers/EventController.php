<?php

namespace App\Http\Controllers;

use App\Builders\EventBuilder;
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
use App\Support\Services\HistoryService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class EventController extends Controller
{
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

        /** @var Event $event */
        $event = Event::create($request->data());

        if ($request->get('projectName')) {
            $project = Project::create(['name' => $request->get('projectName')]);
            $project->users()->save(Auth::user(), ['is_admin' => true]);
            $event->project()->associate($project);
            $event->save();
            $historyService->projectUpdated($project);
        }

        broadcast(new OccupancyUpdated())->toOthers();

        return new CalendarEventResource($event);
    }

    public function destroyEvent(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        broadcast(new OccupancyUpdated())->toOthers();
        $event->forceDelete();

        return new JsonResponse(['success' => 'Event deleted']);
    }

    public function updateEvent(EventUpdateRequest $request, Event $event): CalendarEventResource
    {
        $this->authorize('update', $event);

        $event->update($request->data());

        return new CalendarEventResource($event);
    }

    public function acceptEvent(EventAcceptionRequest $request, Event $event): CalendarEventResource
    {
        if ($request->get('accepted')) {
            $event->occupancy_option = $request->get('accepted');
            $event->save();

            return new CalendarEventResource($event);
        }

        $event->occupancy_option = $request->get('accepted');
        $event->room_id = null;
        $event->save();

        return new CalendarEventResource($event);
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

    public function eventIndex(EventIndexRequest $request): CalendarEventCollectionResource
    {
        $projectId = $request->get('projectId');
        $roomId = $request->get('roomId');
        $roomIds = $request->get('roomIds', []);
        $areaIds = $request->get('areaIds', []);
        $eventTypeIds = $request->get('eventTypeIds', []);
        $roomAttributeIds = $request->get('roomAttributeIds', []);
        $isLoud = $request->get('isLoud');
        $hasAudience = $request->get('hasAudience');

        $events = Event::query()
            // eager loading
            ->withCollisionCount()
            ->with('room')
            // filter for different pages
            ->whereOccursBetween(Carbon::parse($request->get('start')), Carbon::parse($request->get('end')))
            ->when($projectId, fn (EventBuilder $builder) => $builder->where('project_id', $projectId))
            ->when($roomId, fn (EventBuilder $builder) => $builder->where('room_id', $roomId))
            //war in alter Version, relevant für dich Paul ?
            // ->applyFilter($request->filters())
            // user applied filters
            ->unless(empty($roomIds) && empty($areaIds) && empty($roomAttributeIds), fn (EventBuilder $builder) => $builder
                ->whereHas('room', fn (Builder $roomBuilder) => $roomBuilder
                    ->when($roomIds, fn (Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                    ->when($areaIds, fn (Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                    ->when($roomAttributeIds, fn (Builder $roomBuilder) => $roomBuilder
                        ->whereHas('attributes', fn (Builder $roomAttributeBuilder) => $roomAttributeBuilder
                            ->whereIn('room_attributes.id', $roomAttributeIds)))
                )
            )
            ->unless(empty($eventTypeIds), fn (EventBuilder $builder) => $builder->whereIn('event_type_id', $eventTypeIds))
            ->unless(is_null($isLoud), fn (EventBuilder $builder) => $builder->where('is_loud', $isLoud))
            ->unless(is_null($hasAudience), fn (EventBuilder $builder) => $builder->where('audience', $hasAudience))
            ->get();

        return new CalendarEventCollectionResource($events);
    }
}
