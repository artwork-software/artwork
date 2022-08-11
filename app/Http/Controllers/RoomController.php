<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class RoomController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Room::create([
            'name' => $request->name,
            'description' => $request->description,
            'temporary' => $request->temporary,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'area_id' => $request->area_id,
            'user_id' => $request->user_id,
            'order' => Room::max('order') + 1,
        ]);

        return Redirect::route('areas.management')->with('success', 'Room created.');
    }

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
     * Display the specified resource.
     *
     * @param \App\Models\Room $room
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Room $room, Request $request)
    {
        $events = [];
        $event_requests = [];
        if ($request->query('calendarType') === 'monthly') {
            $period = CarbonPeriod::create($request->query('month_start'), $request->query('month_end'));
        }

        $hours = ['0:00', '1:00', '2:00', '3:00', '4:00', '5:00', '6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'];
        $wanted_day = Carbon::parse($request->query('wanted_day'));

        $eventsWithoutRoom = Event::whereNull('room_id')->get();
        $eventsWithoutRoomCount = Event::whereNull('room_id')
            ->whereDate('start_time', '<=', Carbon::parse($request->query('month_end')))
            ->whereDate('end_time', '>=', Carbon::parse($request->query('month_start')))->count();
        $calendarType = '';
        if ($request->query('calendarType')) {
            if ($request->query('calendarType') === 'daily') {
                $calendarType = 'daily';
            } else if ($request->query('calendarType') === 'monthly') {
                $calendarType = 'monthly';
            } else {
                $calendarType = '';
            }
        }
        if ($room->room_admins->contains(Auth::id()) || Auth::user()->can("admin rooms") || Auth::user()->hasRole('admin')) {
            $events = $room->events;
            $event_requests = $room->events->where('occupancy_option', true)->map(fn($event) => [
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
            ]);
        }
        return inertia('Rooms/Show', [
            'room' => [
                'id' => $room->id,
                'conflicts_start_time' => $this->get_conflicts_in_room_for_start_time($room),
                'conflicts_end_time' => $this->get_conflicts_in_room_for_end_time($room),
                'name' => $room->name,
                'description' => $room->description,
                'temporary' => $room->temporary,
                'created_by' => User::where('id', $room->user_id)->first(),
                'created_at' => Carbon::parse($room->created_at)->format('d.m.Y'),
                'start_date' => Carbon::parse($room->start_date)->format('d.m.Y'),
                'start_date_dt_local' => Carbon::parse($room->start_date)->toDateString(),
                'end_date' => Carbon::parse($room->end_date)->format('d.m.Y'),
                'end_date_dt_local' => Carbon::parse($room->end_date)->toDateString(),
                'room_files' => $room->room_files,
                'area_id' => $room->area_id,
                'events' => $this->get_events_for_day_view($wanted_day, $room->events),
                'room_admins' => $room->room_admins->map(fn($room_admin) => [
                    'id' => $room_admin->id,
                    'first_name' => $room_admin->first_name,
                    'last_name' => $room_admin->last_name,
                    'profile_photo_url' => $room_admin->profile_photo_url,
                    'email' => $room_admin->email,
                    'phone_number' => $room_admin->phone_number,
                    'position' => $room_admin->position,
                    'business' => $room_admin->business,
                    'description' => $room_admin->description,
                ]),
                'event_requests' => $event_requests,
                'days_in_month' => $request->query('calendarType') === 'monthly' ? collect($period)->map(fn($date_of_day) => [
                    'date_local' => $date_of_day->toDateTimeLocalString(),
                    'date' => $date_of_day->format('d.m.Y'),
                    'events' => $this->get_events_of_day($date_of_day, $room->events),
                    'conflicts' => $this->conflict_event_ids($room->events),
                ]) : [],
                'area' => $room->area
            ],
            'is_room_admin' => $room->room_admins->contains(Auth::id()),
            'event_types' => EventType::all()->map(fn($event_type) => [
                'id' => $event_type->id,
                'name' => $event_type->name,
                'svg_name' => $event_type->svg_name,
                'project_mandatory' => $event_type->project_mandatory,
                'individual_name' => $event_type->individual_name,
            ]),
            'days_this_month' => $request->query('calendarType') === 'monthly' ? collect($period)->map(fn($date_of_day) => [
                'date_formatted' => strtoupper($date_of_day->isoFormat('dd DD.MM.')),
            ]) : [],
            'projects' => Project::all()->map(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'project_admins' => User::whereHas('projects', function ($q) use ($project) {
                    $q->where('is_admin', 1);
                })->get(),
                'project_managers' => User::whereHas('projects', function ($q) use ($project) {
                    $q->where('is_manager', 1);
                })->get(),
            ]),
            'events_without_room' => $request->query('calendarType') === 'monthly' ? [
                "count" => $eventsWithoutRoomCount,
                'days_in_month' => collect($period)->map(fn($date_of_day) => [
                    'date_local' => $date_of_day->toDateTimeLocalString(),
                    'date' => $date_of_day->format('d.m.Y'),
                    'events' => $this->get_events_of_day($date_of_day, $eventsWithoutRoom),
                ]),
            ] : [
                "count" => $eventsWithoutRoomCount,
                'events' => $this->get_events_for_day_view($wanted_day, $eventsWithoutRoom),
            ],
            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_wanted_day' => $request->query('wanted_day'),
            'hours_of_day' => $hours,
            'shown_day_formatted' => Carbon::parse($request->query('wanted_day'))->format('l d.m.Y'),
            'shown_day_local' => Carbon::parse($request->query('wanted_day')),
            'calendarType' => $calendarType
        ]);
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

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Room $room
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Room $room)
    {
        $room->update($request->only('name', 'description', 'temporary', 'start_date', 'end_date'));

        $room->room_admins()->sync(
            collect($request->room_admins)
                ->map(function ($room_admin) {

                    $this->authorize('update', User::find($room_admin['id']));

                    return $room_admin['id'];
                })
        );

        return Redirect::back()->with('success', 'Room updated');
    }

    /**
     * Duplicates the room whose id is passed in the request
     */
    public function duplicate(Room $room)
    {

        $new_room = Room::create([
            'name' => '(Kopie) ' . $room->name,
            'description' => $room->description,
            'temporary' => $room->temporary,
            'start_date' => $room->start_date,
            'end_date' => $room->end_date,
            'area_id' => $room->area_id,
            'user_id' => Auth::id(),
            'order' => Room::max('order') + 1,
        ]);

        return Redirect::route('areas.management')->with('success', 'Room created.');

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Room $room
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrder(Request $request)
    {

        foreach ($request->rooms as $room) {
            Room::findOrFail($room['id'])->update(['order' => $room['order']]);
        }

        return Redirect::back();
    }

    public function getTrashed()
    {
        return inertia('Trash/Rooms', [
            'trashed_rooms' => Area::all()->map(fn($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => $area->trashed_rooms->map(fn($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'temporary' => (bool)$room->temporary,
                    'start_date' => Carbon::parse($room->start_date)->format('d.m.Y'),
                    'end_date' => Carbon::parse($room->end_date)->format('d.m.Y'),
                    'created_at' => Carbon::parse($room->created_at)->format('d.m.Y, H:i'),
                    'created_by' => User::where('id', $room->user_id)->first(),
                    'room_admins' => $room->room_admins->map(fn($room_admin) => [
                        'id' => $room_admin->id,
                        'first_name' => $room_admin->first_name,
                        'last_name' => $room_admin->last_name,
                        'profile_photo_url' => $room_admin->profile_photo_url,
                        'email' => $room_admin->email,
                        'phone_number' => $room_admin->phone_number,
                        'position' => $room_admin->position,
                        'business' => $room_admin->business,
                        'description' => $room_admin->description,
                    ])
                ])
            ])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Room $room
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Room $room)
    {
        $room->delete();
        return Redirect::route('areas.management')->with('success', 'Room moved to trash');
    }

    public function forceDelete(int $id)
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->forceDelete();
        return Redirect::route('rooms.trashed')->with('success', 'Room restored');
    }

    public function restore(int $id)
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        $room->restore();
        return Redirect::route('rooms.trashed')->with('success', 'Room restored');
    }
}
