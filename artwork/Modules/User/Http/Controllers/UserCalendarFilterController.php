<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserCalendarFilterController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(): void
    {
    }

    public function show(): void
    {
    }

    public function edit(): void
    {
    }

    public function update(Request $request, User $user): void
    {
        $roomIds = $request->collect('rooms')->isNotEmpty() ? $request->collect('rooms') : null;
        $areaIds = $request->collect('areas')->isNotEmpty() ? $request->collect('areas') : null;
        $eventTypes = $request->collect('event_types')->isNotEmpty() ? $request->collect('event_types') : null;
        $roomAttributes = $request->collect('room_attributes')->isNotEmpty() ? $request->collect('room_attributes') : null;
        $roomCategories = $request->collect('room_categories')->isNotEmpty() ? $request->collect('room_categories') : null;
        $eventProperties = $request->collect('event_properties')->isNotEmpty() ? $request->collect('event_properties') : null;


        $user->calendar_filter()->update([
            'event_types' => $eventTypes,
            'rooms' => $roomIds,
            'areas' => $areaIds,
            'room_attributes' => $roomAttributes,
            'room_categories' => $roomCategories,
            'event_properties' => $eventProperties
        ]);
    }

    public function updateDates(Request $request, User $user): void
    {
        $isPlanning = $request->get('isPlanning', false);
        $isDailyView = $request->get('isDailyView', false);

        if ($isPlanning) {
            $filterType = $isDailyView
                ? UserFilterTypes::PLANNING_DAILY_FILTER->value
                : UserFilterTypes::PLANNING_FILTER->value;
        } else {
            $filterType = $isDailyView
                ? UserFilterTypes::CALENDAR_DAILY_FILTER->value
                : UserFilterTypes::CALENDAR_FILTER->value;
        }

        $user->userFilters()->updateOrCreate(
            [
                'filter_type' => $filterType
            ],
            [
                'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($request->end_date)->format('Y-m-d')
            ]
        );
    }

    public function singleValueUpdate(User $user, Request $request): RedirectResponse
    {
        $user->calendar_filter()->update([
            $request->key => $request->value
        ]);

        return redirect()->back();
    }

    public function destroy(): void
    {
    }

    public function reset(User $user): RedirectResponse
    {
        $user->calendar_filter()->update([
            'adjoining_not_loud' => false,
            'adjoining_no_audience' => false,
            'show_free_rooms' => false,
            'show_adjoining_rooms' => false,
            'all_day_free' => false,
            'event_types' => null,
            'rooms' => null,
            'areas' => null,
            'room_attributes' => null,
            'room_categories' => null,
            'event_properties' => null
        ]);

        return redirect()->back();
    }
}
