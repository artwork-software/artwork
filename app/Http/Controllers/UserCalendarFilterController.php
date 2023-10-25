<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserCalendarFilter;
use DebugBar\DebugBar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class UserCalendarFilterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserCalendarFilter  $userCalendarFilter
     * @return \Illuminate\Http\Response
     */
    public function show(UserCalendarFilter $userCalendarFilter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserCalendarFilter  $userCalendarFilter
     * @return \Illuminate\Http\Response
     */
    public function edit(UserCalendarFilter $userCalendarFilter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserCalendarFilter  $userCalendarFilter
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserCalendarFilter  $userCalendarFilter
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserCalendarFilter $userCalendarFilter)
    {
        //
    }

    public function reset(User $user): \Illuminate\Http\RedirectResponse
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

        // reload page
        return redirect()->back();
    }
}
