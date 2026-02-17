<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Artwork\Modules\User\Services\UserShiftCalendarAboService;
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
        $calendar = Calendar::create('Schichtplan ' . $user->full_name)
            ->refreshInterval(5)
            ->appendProperty(TextProperty::create('METHOD', 'PUBLISH'));
        // Get all shifts for the user
        $shifts = $this->userShiftCalendarAboService->getFilteredShifts($calendarAbo, $user->shifts);

        // Process each shift and add to the calendar
        foreach ($shifts as $shift) {
            if ($this->userShiftCalendarAboService->shouldAddShift($calendarAbo, $shift)) {
                $this->userShiftCalendarAboService->addShiftToCalendar($calendar, $calendarAbo, $shift);
            }
        }

        return response($calendar->get(), 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="schichtplan.ics"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
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
