<?php

namespace App\Http\Controllers;

use App\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\ShiftFilter;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ShiftFilterController extends Controller
{

    /**
     * Get all filters of the current user
     */
    public function index() {
        return ShiftFilter::where('user_id', Auth::id())->get()->map(fn (ShiftFilter $filter) => [
            'id' => $filter->id,
            'name' => $filter->name,
            'rooms' => $filter->rooms->map(fn (Room $room) => [
                'id' => $room->id,
                'everyone_can_book' => $room->everyone_can_book,
                'label' => $room->name,
                'room_admins' => $room->users()->wherePivot('is_admin', true)->get(),
            ]),
            'eventTypes' => $filter->event_types,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Debugbar::info('Storing Shift filter');

        $filter = ShiftFilter::create([
            'name' => $request->name,
            'user_id' => Auth::id()
        ]);

        $roomIds = $request->input('calendarFilters.roomIds');
        $eventTypeIds = $request->input('calendarFilters.eventTypeIds');

        if($roomIds) {
            foreach($roomIds as $roomId)
            {
                $filter->rooms()->save(Room::where('id', $roomId)->first());
            }
        }

        if($eventTypeIds) {
            foreach($eventTypeIds as $eventTypeId)
            {
                $filter->event_types()->save(EventType::where('id', $eventTypeId)->first());
            }
        }

        return Redirect::back()->with('success', 'Shift Filter created.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Filter  $filter
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ShiftFilter $filter)
    {
        $filter->delete();
    }
}
