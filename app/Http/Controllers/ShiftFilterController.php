<?php

namespace App\Http\Controllers;

use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\ShiftFilter;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ShiftFilterController extends Controller
{
    public function index(): Collection
    {
        return ShiftFilter::query()->where('user_id', Auth::id())->get()->map(fn (ShiftFilter $filter) => [
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

    public function store(Request $request): RedirectResponse
    {
        Debugbar::info('Storing Shift filter');

        $filter = ShiftFilter::create([
            'name' => $request->name,
            'user_id' => Auth::id()
        ]);

        $roomIds = $request->input('calendarFilters.roomIds');
        $eventTypeIds = $request->input('calendarFilters.eventTypeIds');

        if ($roomIds) {
            foreach ($roomIds as $roomId) {
                $filter->rooms()->save(Room::where('id', $roomId)->first());
            }
        }

        if ($eventTypeIds) {
            foreach ($eventTypeIds as $eventTypeId) {
                $filter->event_types()->save(EventType::where('id', $eventTypeId)->first());
            }
        }

        return Redirect::back();
    }

    public function destroy(ShiftFilter $filter): void
    {
        $filter->delete();
    }
}
