<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\UserCalendarAbo\Models\UserCalendarAbo;
use Artwork\Modules\UserCalendarAbo\Services\UserCalendarAboService;
use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\IcalendarGenerator\Components\Calendar;

class UserCalenderAboController extends Controller
{
    public function __construct(
        private readonly UserCalendarAboService $userCalendarAboService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
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
        $this->userCalendarAboService->create($request->all(), Auth::id());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $calendar_abo_id): \Illuminate\Contracts\Routing\ResponseFactory|
    \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Foundation\Application
    {
        $calendarAbo = UserCalendarAbo::where('calendar_abo_id', $calendar_abo_id)->firstOrFail();
        $user = $calendarAbo->user;

        // Create Calendar
        $calendar = Calendar::create('Alle Termine')->refreshInterval(5);


        // get all Events
        $events = $this->userCalendarAboService->getFilteredEvents($calendarAbo, Event::all());

        // filter event room on selected areas
        $events = $events->filter(function ($event) use ($calendarAbo) {
            if ($calendarAbo->specific_areas) {
                return in_array($event->room->area_id, $calendarAbo->selected_areas, true);
            }
            return true;
        });

        // Process each event and add events to the calendar
        foreach ($events as $event) {
            $this->userCalendarAboService->addEventToCalendar($calendar, $event);
        }


        // Generate filename and response
        $filename = $user->full_name . '-Termine.ics';
        return response($calendar->get(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserCalendarAbo $userCalenderAbo): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserCalendarAbo $userCalenderAbo): void
    {
        $this->userCalendarAboService->updateByRequest($request->all(), $userCalenderAbo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserCalendarAbo $userCalenderAbo): void
    {
        //
    }
}
