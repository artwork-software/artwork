<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\UserCalendarAbo;
use Artwork\Modules\User\Services\UserCalendarAboService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Properties\TextProperty;

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

        // Create Calendar
        $calendar = Calendar::create('Alle Termine')
            ->refreshInterval(5)
            ->appendProperty(TextProperty::create('METHOD', 'PUBLISH'));

        // Get filtered events via DB query (eager loaded, no N+1)
        $events = $this->userCalendarAboService
            ->getFilteredEventsQuery($calendarAbo)
            ->get();

        // Process each event and add events to the calendar
        foreach ($events as $event) {
            $this->userCalendarAboService->addEventToCalendar($calendar, $event);
        }

        return response($calendar->get(), 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="termine.ics"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
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
