<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\User;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EventController extends Controller
{

    private function get_events_of_day($date_of_day, $events): array
    {

        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        foreach ($events as $event) {
            if (in_array($today, $event->days_of_event)) {
                $eventsToday[] = [
                    'id' => $event->id,
                    'name' => $event->name,
                    'description' => $event->description,
                    "start_time" => $event->start_time,
                    "start_time_dt_local" => Carbon::parse($event->start_time)->toDateTimeLocalString(),
                    "end_time" => $event->end_time,
                    "end_time_dt_local" => Carbon::parse($event->end_time)->toDateTimeLocalString(),
                    "occupancy_option" => $event->occupancy_option,
                    "audience" => $event->audience,
                    "is_loud" => $event->is_loud,
                    "event_type_id" => $event->event_type_id,
                    "room_id" => $event->room_id,
                    "user_id" => $event->user_id,
                    "project_id" => $event->project_id,
                    "created_at" => $event->created_at,
                    "updated_at" => $event->updated_at,
                ];
            }
        }


        return $eventsToday;
    }

    private function get_events_for_day_view($date_of_day, $events): array
    {
        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        $lastEvent = null;

        foreach ($events as $event) {
            if (in_array($today, $event->days_of_event)) {

                $conflicts = [];

                if (!blank($lastEvent)) {

                    $this_event_start_time = Carbon::parse($event['start_time']);
                    $last_event_end_time = Carbon::parse($lastEvent['end_time']);

                    if ($last_event_end_time->greaterThanOrEqualTo($this_event_start_time)) {
                        $conflicts[] = $lastEvent['id'];
                    }

                }

                $eventsToday[] = [
                    'id' => $event->id,
                    'conflicts' => $conflicts,
                    'name' => $event->name,
                    'description' => $event->description,
                    "start_time" => $event->start_time,
                    "start_time_dt_local" => Carbon::parse($event->start_time)->toDateTimeLocalString(),
                    "end_time" => $event->end_time,
                    "end_time_dt_local" => Carbon::parse($event->end_time)->toDateTimeLocalString(),
                    "occupancy_option" => $event->occupancy_option,
                    "minutes_from_day_start" => Carbon::parse($date_of_day)->startOfDay()->subHours(2)->diffInMinutes(Carbon::parse($event->start_time)),
                    "duration_in_minutes" => Carbon::parse($event->start_time)->diffInMinutes(Carbon::parse($event->end_time)),
                    "audience" => $event->audience,
                    "is_loud" => $event->is_loud,
                    "event_type_id" => $event->event_type_id,
                    "event_type" => $event->event_type,
                    "room_id" => $event->room_id,
                    "user_id" => $event->user_id,
                    "project_id" => $event->project_id,
                    "created_at" => $event->created_at,
                    "updated_at" => $event->updated_at,
                ];

                $lastEvent = $event;
            }
        }

        return $eventsToday;
    }

    /**
     * Display a listing of the resource.
     * Returns all Events
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function month_index(Request $request)
    {

        $period = CarbonPeriod::create($request->query('month_start'), $request->query('month_end'));

        $eventsWithoutRoom = Event::whereNull('room_id')->get();
        $eventsWithoutRoomCount = Event::whereNull('room_id')->count();

        return inertia('Events/EventManagement', [
            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_start_time' => $request->query('month_start'),
            'requested_end_time' => $request->query('month_end'),
            'days_this_month' => collect($period)->map(fn($date_of_day) => [
                'date_formatted' => strtoupper($date_of_day->isoFormat('dd DD.MM.')),
            ]),
            'rooms' => Room::with(['events' => function ($query) {
                $query->orderBy('end_time', 'ASC');
            }])->get()->map(fn($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'area_id' => $room->area_id,
                'days_in_month' => collect($period)->map(function ($date_of_day) use ($room) {
                    $events = $this->get_events_of_day($date_of_day, $room->events);

                    $conflicts = $this->conflict_event_ids($events);

                    return [
                        'date_local' => $date_of_day->toDateTimeLocalString(),
                        'date' => $date_of_day->format('d.m.Y'),
                        'conflicts' => $conflicts,
                        'events' => $events
                    ];
                }),
            ]),
            'events_without_room' => [
                "count" => $eventsWithoutRoomCount,
                'days_in_month' => collect($period)->map(fn($date_of_day) => [
                    'date_local' => $date_of_day->toDateTimeLocalString(),
                    'date' => $date_of_day->format('d.m.Y'),
                    'events' => $this->get_events_of_day($date_of_day, $eventsWithoutRoom),
                ]),
            ],
            'event_types' => EventType::paginate(10)->through(fn($event_type) => [
                'id' => $event_type->id,
                'name' => $event_type->name,
                'svg_name' => $event_type->svg_name,
                'project_mandatory' => $event_type->project_mandatory,
                'individual_name' => $event_type->individual_name,
            ]),
            'areas' => Area::paginate(10)->through(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => $area->rooms()->with('room_admins', 'events')->orderBy('order')->get()->map(fn($room) => [
                    'conflicts_start_time' => $this->get_conflicts_in_room_for_start_time($room),
                    'conflicts_end_time' => $this->get_conflicts_in_room_for_end_time($room),
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => $room->temporary,
                    'created_by' => User::where('id', $room->user_id)->first(),
                    'created_at' => Carbon::parse($room->created_at)->format('d.m.Y, H:i'),
                    'start_date' => Carbon::parse($room->start_date)->format('d.m.Y'),
                    'start_date_dt_local' => Carbon::parse($room->start_date)->toDateString(),
                    'end_date' => Carbon::parse($room->end_date)->format('d.m.Y'),
                    'end_date_dt_local' => Carbon::parse($room->end_date)->toDateString(),
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'profile_photo_url' => $room_admin->profile_photo_url
                    ])
                ])
            ]),
            'projects' => Project::paginate(10)->through(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
        ]);
    }

    public function get_conflicts_in_room_for_start_time(Room $room): array
    {

        $start_time = request('start_time');

        $conflicting_events = [];

        foreach ($room->events as $event) {

            if (Carbon::parse($start_time)->between(Carbon::parse($event->start_time), Carbon::parse($event->end_time))) {

                $conflicting_events[] = [
                    'event_name' => $event->name,
                    'event_type' => $event->event_type,
                    'project' => $event->project
                ];

            }

        }

        return $conflicting_events;

    }

    public function get_conflicts_in_room_for_end_time(Room $room): array
    {
        $end_time = request('end_time');

        $conflicting_events = [];

        foreach ($room->events as $event) {

            if (Carbon::parse($end_time)->between(Carbon::parse($event->start_time), Carbon::parse($event->end_time))) {

                $conflicting_events[] = [
                    'event_name' => $event->name,
                    'event_type' => $event->event_type,
                    'project' => $event->project
                ];

            }

        }

        return $conflicting_events;

    }

    private function conflict_event_ids($events_with_ascending_end_time): array
    {

        $conflict_event_ids = array();

        $lastEvent = null;

        foreach ($events_with_ascending_end_time as $event) {

            if (!blank($lastEvent)) {

                $this_event_start_time = Carbon::parse($event['start_time']);
                $last_event_end_time = Carbon::parse($lastEvent['end_time']);

                if ($last_event_end_time->greaterThanOrEqualTo($this_event_start_time)) {
                    $conflict_event_ids[] = [$lastEvent['id'], $event['id']];
                }

            }

            $lastEvent = $event;
        }


        return $conflict_event_ids;
    }

    public function day_index(Request $request)
    {

        $hours = ['0:00', '1:00', '2:00', '3:00', '4:00', '5:00', '6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'];

        $wanted_day = Carbon::parse($request->query('wanted_day'));

        return inertia('Events/DayManagement', [
            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_wanted_day' => $request->query('wanted_day'),
            'day_formatted' => $wanted_day->isoFormat('dd DD.MM.YYYY'),
            'hours_of_day' => $hours,
            'rooms' => Room::with(['events' => function ($query) {
                $query->orderBy('end_time', 'ASC')->with('event_type');
            }])->get()->map(fn($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'area_id' => $room->area_id,
                'events' => $this->get_events_for_day_view($wanted_day, $room->events)
            ]),
            'projects' => Project::paginate(10)->through(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'project_leaders' => User::whereHas('projects', function ($q) use ($project) {
                    $q->where('is_manager', 1);
                })->get(),
            ]),
            'event_types' => EventType::paginate(10)->through(fn($event_type) => [
                'id' => $event_type->id,
                'name' => $event_type->name,
                'svg_name' => $event_type->svg_name,
                'project_mandatory' => $event_type->project_mandatory,
                'individual_name' => $event_type->individual_name,
            ]),
            'areas' => Area::paginate(10)->through(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => $area->rooms()->with('room_admins', 'events.event_type')->orderBy('order')->get()->map(fn($room) => [
                    'conflicts_start_time' => $this->get_conflicts_in_room_for_start_time($room),
                    'conflicts_end_time' => $this->get_conflicts_in_room_for_end_time($room),
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => $room->temporary,
                    'created_by' => User::where('id', $room->user_id)->first(),
                    'created_at' => Carbon::parse($room->created_at)->format('d.m.Y, H:i'),
                    'start_date' => Carbon::parse($room->start_date)->format('d.m.Y'),
                    'start_date_dt_local' => Carbon::parse($room->start_date)->toDateString(),
                    'end_date' => Carbon::parse($room->end_date)->format('d.m.Y'),
                    'end_date_dt_local' => Carbon::parse($room->end_date)->toDateString(),
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'profile_photo_url' => $room_admin->profile_photo_url
                    ])
                ])
            ]),
        ]);
    }

    public function day_events($date)
    {
        $hours = ['0:00', '1:00', '2:00', '3:00', '4:00', '5:00', '6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'];

        return Room::with('events')->get()->map(fn($room) => [
            'name' => $room->name,
            'hours' => collect($hours)->map(fn($hour) => [
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
    public function requests_index()
    {
        return inertia('Events/EventRequestsManagement', [
            'event_requests' => Event::where('occupancy_option', true)->paginate(10)->through(fn($event) => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'start_time' => Carbon::parse($event->start_time)->format('d.m.Y, H:i'),
                'start_time_weekday' => Carbon::parse($event->start_time)->format('l'),
                'end_time' => Carbon::parse($event->end_time)->format('d.m.Y, H:i'),
                'end_time_weekday' => Carbon::parse($event->end_time)->format('l'),
                'start_time_dt_local' => Carbon::parse($event->start_time)->toDateTimeLocalString(),
                'end_time_dt_local' => Carbon::parse($event->end_time)->toDateTimeLocalString(),
                'occupancy_option' => $event->occupancy_option,
                'audience' => $event->audience,
                'is_loud' => $event->is_loud,
                'event_type' => $event->event_type,
                'room' => $event->room,
                'project' => $event->project,
                'created_at' => Carbon::parse($event->created_at)->format('d.m.Y, H:i'),
                'created_by' => $event->creator
            ]),
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
     * @param \Illuminate\Http\Request $request
     */
    public function store(Request $request)
    {
        $start_time_parse = Carbon::parse($request->start_time);
        $end_time_parse = Carbon::parse($request->end_time);

        if ($start_time_parse->lessThan($end_time_parse)) {
            if ($request->project_name == null) {
                $this->store_on_existing_project($request);
            } else {
                $this->store_on_new_project($request);
            }
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
     * @param \App\Models\Event $event
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Event $event)
    {
        return inertia('Events/Show', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'start_time' => Carbon::parse($event->start_time)->format('d.m.Y, H:i'),
                'end_time' => Carbon::parse($event->end_time)->format('d.m.Y, H:i'),
                'occupancy_option' => $event->occupancy_option,
                'audience' => $event->audience,
                'is_loud' => $event->is_loud,
                'event_type' => $event->event_type,
                'room' => $event->room,
                'project' => $event->project
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Event $event
     * @return \Illuminate\Http\Response
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Event $event
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
            'project_id'));

        return Redirect::back()->with('success', 'Event updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Event $event)
    {
        $event->delete();
        return Redirect::back()->with('success', 'Event deleted');
    }
}
