<?php

namespace App\Http\Controllers;

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
        $user->calendar_filter()->update($request->only([
            'is_loud',
            'is_not_loud',
            'adjoining_not_loud',
            'has_audience',
            'has_no_audience',
            'adjoining_no_audience',
            'show_free_rooms',
            'show_adjoining_rooms',
            'all_day_free',
            'event_types',
            'rooms',
            'areas',
            'room_attributes',
            'room_categories',
        ]));
    }

    public function updateDates(Request $request, User $user): void
    {
        $user->calendar_filter()->update([
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d')
        ]);
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
            'is_loud' => false,
            'is_not_loud' => false,
            'adjoining_not_loud' => false,
            'has_audience' => false,
            'has_no_audience' => false,
            'adjoining_no_audience' => false,
            'show_free_rooms' => false,
            'show_adjoining_rooms' => false,
            'all_day_free' => false,
            'event_types' => null,
            'rooms' => null,
            'areas' => null,
            'room_attributes' => null,
            'room_categories' => null,
        ]);

        return redirect()->back();
    }
}
