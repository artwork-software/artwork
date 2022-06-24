<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\EventType;
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
     * @param  \Illuminate\Http\Request  $request
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
            if(in_array($today, $event->days_of_event)) {
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
                    "created_at" =>  $event->created_at,
                    "updated_at" => $event->updated_at,
                ];
            }
        }

        return $eventsToday;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Room  $room
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Room $room,Request $request)
    {
        $events = [];
        $event_requests = [];
        $period = CarbonPeriod::create($request->query('month_start'), $request->query('month_end'));
        if($room->room_admins->contains(Auth::id())) {
            $events = $room->events;
            $event_requests = $room->events->where('occupancy_option',true)->map(fn($event) => [
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
                'days_in_month' => collect($period)->map(fn($date_of_day) => [
                    'date_local' => $date_of_day->toDateTimeLocalString(),
                    'date' => $date_of_day->format('d.m.Y'),
                    'events' => $this->get_events_of_day($date_of_day, $room->events)
                ]),
                'area' => $room->area
            ],
            'event_types' => EventType::all(),
            'days_this_month' => collect($period)->map(fn($date_of_day) => [
                'date_formatted' => strtoupper($date_of_day->isoFormat('dd DD.MM.')),
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Room  $room
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
    public function duplicate(Room $room) {

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

        return Redirect::route('rooms.show', $new_room->id)->with('success', 'Room created.');

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
            'trashed_rooms' => Area::paginate(10)->through(fn($area) => [
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
     * @param  \App\Models\Room  $room
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
