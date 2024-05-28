<?php

namespace App\Http\Controllers;

use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Artwork\Modules\UserShiftCalendarAbo\Services\UserShiftCalendarAboService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Properties\Parameter;
use Spatie\IcalendarGenerator\Properties\TextProperty;

class UserShiftCalendarAboController extends Controller
{
    public function __construct(
        private readonly UserShiftCalendarAboService $userShiftCalendarAboService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        $this->userShiftCalendarAboService->create($request->all(), Auth::id());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $calendar_abo_id)
    {
        // Retrieve Calendar Abo and related user
        $calendarAbo = UserShiftCalendarAbo::where('calendar_abo_id', $calendar_abo_id)->firstOrFail();
        $user = $calendarAbo->user;

        // Create Calendar
        $calendar = Calendar::create('Schichtplan ' . $user->full_name)->refreshInterval(5);
        // Get all shifts for the user
        $shifts = $this->userShiftCalendarAboService->getFilteredShifts($calendarAbo, $user->shifts);

        // Process each shift and add events to the calendar
        foreach ($shifts as $shift) {
            $shiftEvent = $shift->event()->first();
            if ($this->userShiftCalendarAboService->shouldAddShiftEvent($calendarAbo, $shiftEvent)) {
                $this->userShiftCalendarAboService->addEventToCalendar($calendar, $calendarAbo, $shift, $shiftEvent);
            }
        }

        // Generate filename and response
        $filename = $user->full_name . '-Schichtplan.ics';
        return response($calendar->get(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserShiftCalendarAbo $userShiftCalendarAbo): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserShiftCalendarAbo $userShiftCalendarAbo): void
    {
        $this->userShiftCalendarAboService->updateByRequest($userShiftCalendarAbo, $request->all());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserShiftCalendarAbo $userShiftCalendarAbo): void
    {
        //
    }
}
