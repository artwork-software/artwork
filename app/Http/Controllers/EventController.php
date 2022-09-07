<?php

namespace App\Http\Controllers;

use App\Events\OccupancyUpdated;
use App\Http\Requests\StoreOrUpdateEvent;
use App\Http\Resources\EventCollectionForDailyCalendarResource;
use App\Http\Resources\EventCollectionForMonthlyCalendarResource;
use App\Http\Resources\EventIndexResource;
use App\Http\Resources\EventShowResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectIndexAdminResource;
use App\Http\Resources\RoomIndexEventDayResource;
use App\Http\Resources\RoomIndexEventMonthlyResource;
use App\Http\Resources\RoomIndexResource;
use App\Http\Resources\TaskIndexResource;
use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     * Returns all Events
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function indexEventsInMonth(Request $request)
    {
        $period = CarbonPeriod::create($request->query('month_start'), $request->query('month_end'));

        $eventsWithoutRoom = Event::query()
            ->with('sameRoomEvents')
            ->whereNull('room_id')
            ->whereOccursBetween($period->start, $period->end)
            ->get();

        $areas = Area::query()
            ->with([
                'rooms.room_admins',
                'rooms.events.creator',
                'rooms.creator',
                'rooms.events.event_type',
                'rooms.events.room'
            ])
            ->get();

        $rooms = Room::query()
            ->with(['events.event_type', 'events.sameRoomEvents', 'events.creator', 'room_admins'])
            ->get();

        $projects = Project::query()->with(['adminUsers', 'managerUsers'])->get();

        return inertia('Events/EventManagement', [
            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_start_time' => $request->query('month_start'),
            'requested_end_time' => $request->query('month_end'),

            'days_this_month' => $period->map(fn (Carbon $date) => ['date_formatted' => Str::upper($date->isoFormat('dd DD.MM.'))]),

            'myRooms' => Auth::user()->admin_rooms->pluck('id'),

            'rooms' => RoomIndexEventMonthlyResource::collection($rooms)->resolve(),
            'projects' => ProjectIndexAdminResource::collection($projects)->resolve(),
            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),
            'events_without_room' => new EventCollectionForMonthlyCalendarResource($eventsWithoutRoom),

            'areas' => $areas->map(fn (Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexResource::collection($area->rooms->sortBy('order'))->resolve(),
            ]),
        ]);
    }

    public function indexEventsWithStartConflicts(Room $room): array
    {
        $start_time = Carbon::parse(request('start_time'));
        $events = $room->events->filter(fn (Event $event) => $event->occursAtTime($start_time));

        return EventIndexResource::collection($events)->resolve();
    }

    public function indexEventsWithEndConflicts(Room $room): array
    {
        $start_time = Carbon::parse(request('end_time'));
        $events = $room->events->filter(fn (Event $event) => $event->occursAtTime($start_time));

        return EventIndexResource::collection($events)->resolve();
    }

    public function indexEventForDay(Request $request)
    {
        $today = Carbon::parse($request->query('wanted_day'));
        $eventsWithoutRoom = Event::query()
            ->with('sameRoomEvents')
            ->whereNull('room_id')
            ->occursAt($today, false)
            ->get();

        return inertia('Events/DayManagement', [
            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_wanted_day' => $today->toDateTimeLocalString(),
            'hours_of_day' => config('calendar.hours'),
            'shown_day_formatted' => $today->format('l d.m.Y'),
            'shown_day_local' => $today,

            'myRooms' => Auth::user()->admin_rooms->pluck('id'),

            'rooms' => RoomIndexEventDayResource::collection(Room::query()->with(['events.event_type'])->get())->resolve(),

            'projects' => ProjectIndexAdminResource::collection(Project::all())->resolve(),

            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),

            'events_without_room' => new EventCollectionForDailyCalendarResource($eventsWithoutRoom),

            'areas' => Area::all()->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexResource::collection($area->rooms()->with('room_admins', 'events')->orderBy('order')->get())->resolve(),
            ]),
        ]);
    }

    public function showDashboard(Request $request)
    {
        /** @var \Carbon\Carbon $today */
        $today = now($request->get('timezone', config('calendar.default_timezone')));

        $eventsWithoutRoom = Event::query()
            ->with('creator', 'sameRoomEvents')
            ->whereNull('room_id')
            ->occursAt($today, false)
            ->get();

        $areas = Area::query()
            ->with([
                'rooms.room_admins',
                'rooms.events.creator',
                'rooms.creator',
                'rooms.events.event_type',
                'rooms.events.room'
            ])
            ->get();

        $rooms = Room::query()->with(['events.event_type', 'events.sameRoomEvents', 'events.creator'])->get();
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
            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_wanted_day' => $today->toDateTimeLocalString(),
            'hours_of_day' => config('calendar.hours'),
            'shown_day_formatted' => $today->format('l d.m.Y'),
            'shown_day_local' => $today,

            'rooms' => RoomIndexEventDayResource::collection($rooms)->resolve(),
            'projects' => ProjectIndexAdminResource::collection($projects)->resolve(),
            'tasks' => TaskIndexResource::collection($tasks)->resolve(),
            'events_without_room' => new EventCollectionForDailyCalendarResource($eventsWithoutRoom),

            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),

            'areas' => $areas->map(fn (Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexResource::collection($area->rooms->sortBy('order'))->resolve(),
            ]),
        ]);
    }

    public function day_events($date)
    {
        return Room::with('events')->get()->map(fn ($room) => [
            'name' => $room->name,
            'hours' => collect(config('calendar.hours'))->map(fn ($hour) => [
                'hour' => $hour,
                'events' => $room->events()
                    ->whereDate('start_time', '<=', $date)
                    ->whereDate('end_time', '>=', $date)
                    ->whereTime('start_time', '<=', Carbon::parse($hour)->toTimeString())
                    ->whereTime('end_time', '>=', Carbon::parse($hour)->toTimeString())
                    ->get()
            ])
        ]);
    }

    /**
     * Display a listing of the resource.
     * Only returns the events that are an occupancy option
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function indexEventRequests()
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(StoreOrUpdateEvent $request)
    {
        $request->validated();

        $start_time_parse = Carbon::parse($request->start_time);
        $end_time_parse = Carbon::parse($request->end_time);

        if ($start_time_parse->lessThan($end_time_parse)) {
            if ($request->project_name == null) {
                $this->store_on_existing_project($request);
            } else {
                $this->store_on_new_project($request);
            }

            broadcast(new OccupancyUpdated())->toOthers();

            return Redirect::back()->with('success', 'Event created.');
        } else {
            return response()->json(['error' => 'Start date has to be before end date.'], 403);
        }
    }

    private function store_on_existing_project(Request $request)
    {
        Event::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'occupancy_option' => $request->occupancy_option,
            'audience' => $request->audience,
            'is_loud' => $request->is_loud,
            'event_type_id' => $request->event_type_id,
            'room_id' => $request->room_id,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id
        ]);
    }

    private function store_on_new_project(Request $request)
    {
        $project = Project::create([
            'name' => $request->project_name
        ]);

        $project->users()->save(Auth::user(), ['is_admin' => true]);

        $project->project_histories()->create([
            "user_id" => Auth::id(),
            "description" => "Projekt angelegt"
        ]);

        Event::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'occupancy_option' => $request->occupancy_option,
            'audience' => $request->audience,
            'is_loud' => $request->is_loud,
            'event_type_id' => $request->event_type_id,
            'room_id' => $request->room_id,
            'project_id' => $project->id,
            'user_id' => $request->user_id
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Event  $event
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Event $event)
    {
        return inertia('Events/Show', [
            'event' => new EventShowResource($event),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Event $event)
    {
        $event->update($request->only(
            'name',
            'description',
            'start_time',
            'end_time',
            'occupancy_option',
            'audience',
            'is_loud',
            'event_type_id',
            'room_id',
            'project_id')
        );

        return Redirect::back()->with('success', 'Event updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Area  $area
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Event $event)
    {
        broadcast(new OccupancyUpdated())->toOthers();
        $event->delete();

        return Redirect::back()->with('success', 'Event deleted');
    }
}
