<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserShiftCalendarFilterController extends Controller
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
     * @param  \App\Models\UserShiftCalendarFilter  $userShiftCalendarFilter
     * @return \Illuminate\Http\Response
     */
    public function show(UserShiftCalendarFilter $userShiftCalendarFilter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserShiftCalendarFilter  $userShiftCalendarFilter
     * @return \Illuminate\Http\Response
     */
    public function edit(UserShiftCalendarFilter $userShiftCalendarFilter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserShiftCalendarFilter  $userShiftCalendarFilter
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $user->shift_calendar_filter()->update($request->only([
            'event_types',
            'rooms',
        ]));
    }

    public function updateDates(Request $request, User $user): void
    {
        $user->calendar_filter()->update([
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d')
        ]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserShiftCalendarFilter  $userShiftCalendarFilter
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserShiftCalendarFilter $userShiftCalendarFilter)
    {
        //
    }

    public function reset(User $user): \Illuminate\Http\RedirectResponse
    {
        $user->shift_calendar_filter()->update([
            'event_types' => null,
            'rooms' => null,
        ]);

        // reload page
        return redirect()->back();
    }
}
