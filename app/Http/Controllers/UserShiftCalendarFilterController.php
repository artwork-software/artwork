<?php

namespace App\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserShiftCalendarFilterController extends Controller
{
    public function update(Request $request, User $user): void
    {
        $user->shift_calendar_filter()->update($request->only([
            'event_types',
            'rooms',
        ]));
    }

    public function updateDates(Request $request, User $user): void
    {
        $user->shift_calendar_filter()->update([
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d')
        ]);
    }

    public function singleValueUpdate(Request $request, User $user): void
    {
        $user->shift_calendar_filter()->update([
            $request->key => $request->value
        ]);
    }

    public function reset(User $user): RedirectResponse
    {
        $user->shift_calendar_filter()->update([
            'event_types' => null,
            'rooms' => null,
        ]);

        return redirect()->back();
    }
}
