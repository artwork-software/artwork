<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     * Only returns the events that are an occupancy option.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('Events/EventManagement', [
            'events' => Event::where('occupancy_option', true)->paginate(10)->through(fn($event) => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'start_time' => Carbon::parse($event->start_date)->format('d.m.Y, H:i'),
                'end_time' => Carbon::parse($event->start_date)->format('d.m.Y, H:i'),
                'occupancy_option' => $event->occupancy_option,
                'audience' => $event->audience,
                'is_loud' => $event->is_loud,
                'event_type' => $event->event_type,
                'room' => $event->room,
                'project' => $event->project
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
                'rooms' => $area->rooms()->orderBy('order')->get()->map(fn($room) => [
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $event = Event::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_time' => $request->start_date,
            'end_time' => $request->end_date,
            'occupancy_option' => $request->occupancy_option,
            'audience' => $request->audience,
            'is_loud' => $request->is_loud,
            'event_type_id' => $request->event_type_id,
            'room_id' => $request->room_id,
            'project_id' => $request->project_id
        ]);

        return Redirect::route('events.show', $event)->with('success', 'Event created.');
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
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'start_time' => Carbon::parse($event->start_date)->format('d.m.Y, H:i'),
                'end_time' => Carbon::parse($event->start_date)->format('d.m.Y, H:i'),
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
            'project_id'));

        return Redirect::route('events.management')->with('success', 'Event updated');
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
        return Redirect::route('$events.management')->with('success', 'Event deleted');
    }
}
