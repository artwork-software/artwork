<?php

namespace App\Http\Controllers;

use App\Enums\CalendarTimeEnum;
use App\Http\Resources\EventCollectionForDailyCalendarResource;
use App\Http\Resources\EventCollectionForMonthlyCalendarResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectIndexAdminResource;
use App\Http\Resources\RoomCalendarResource;
use App\Http\Resources\RoomIndexResource;
use App\Http\Resources\RoomIndexWithoutEventsResource;
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
use Illuminate\Support\Str;

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

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Room  $room
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Room $room, Request $request)
    {
        $period = CarbonPeriod::create($request->query('month_start'), $request->query('month_end'));
        $calendarType = $request->query('calendarType');

        $eventsWithoutRoom = Event::query()
            ->with('sameRoomEvents')
            ->whereNull('room_id')
            ->whereOccursBetween($period->start, $period->end)
            ->get();

        $room->load('creator');

        return inertia('Rooms/Show', [
            'room' => new RoomCalendarResource($room),

            'areas' => Area::all()->map(fn (Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexResource::collection($area->rooms()->with('room_admins', 'events')->orderBy('order')->get())->resolve(),
            ]),

            'is_room_admin' => $room->room_admins->contains(Auth::id()),

            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),

            'days_this_month' => $calendarType === CalendarTimeEnum::MONTHLY
                ? $period->map(fn (Carbon $date) => ['date_formatted' => Str::upper($date->isoFormat('dd DD.MM.'))])
                : [],

            'projects' => ProjectIndexAdminResource::collection(Project::all())->resolve(),

            'events_without_room' => $calendarType === CalendarTimeEnum::MONTHLY
                ? new EventCollectionForMonthlyCalendarResource($eventsWithoutRoom)
                : new EventCollectionForDailyCalendarResource($eventsWithoutRoom),

            'start_time_of_new_event' => $request->query('start_time'),
            'end_time_of_new_event' => $request->query('end_time'),
            'requested_wanted_day' => $request->query('wanted_day'),
            'hours_of_day' => config('calendar.hours'),
            'shown_day_formatted' => Carbon::parse($request->query('wanted_day'))->format('l d.m.Y'),
            'shown_day_local' => Carbon::parse($request->query('wanted_day')),
            'calendarType' => $calendarType,
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
    public function duplicate(Room $room)
    {
        Room::create([
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Room  $room
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
            'trashed_rooms' => Area::all()->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'rooms' => RoomIndexWithoutEventsResource::collection($area->trashed_rooms)->resolve(),
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
