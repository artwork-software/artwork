<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Enums\UserFilterTypes;
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
        $user->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_FILTER->value],
            [
                'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($request->end_date)->format('Y-m-d')
            ]
        );
    }

    public function updateUserWorkerShiftPlanFilters(Request $request, User $user): void
    {
        $user->workerShiftPlanFilter()->update([
            'start_date' => $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null,
            'end_date' => $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null
        ]);
    }

    public function updateInventoryArticlePlanFilters(Request $request, User $user): void
    {
        $user->inventoryArticlePlanFilter()->updateOrCreate([], [
            'start_date' => Carbon::parse($request->get('start_date'))->format('Y-m-d'),
            'end_date' => Carbon::parse($request->get('end_date'))->format('Y-m-d')
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
