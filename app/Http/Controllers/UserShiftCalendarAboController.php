<?php

namespace App\Http\Controllers;

use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Artwork\Modules\UserShiftCalendarAbo\Services\UserShiftCalendarAboService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\IcalendarGenerator\Components\Calendar;

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
        $this->userShiftCalendarAboService->create($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $calendar_abo_id)
    {
        $calendarAbo = UserShiftCalendarAbo::where('calendar_abo_id', $calendar_abo_id)->firstOrFail();
        $user = $calendarAbo->user;
        $calendar = Calendar::create('Schichtplan ' . $calendarAbo->user->full_name)->refreshInterval(5);

        $shifts = $user->shifts;
        $shiftsAll = $shifts;
        // if date range is set
        if ($calendarAbo->date_range) {
            $shiftsAll = $shifts->whereBetween('start_date', [$calendarAbo->start_date, $calendarAbo->end_date])
                ->whereBetween('end_date', [$calendarAbo->start_date, $calendarAbo->end_date]);
        }


        $shiftsAll = $shiftsAll->sortBy('start_date');

        // alert Time from notification time and unit

        foreach ($shiftsAll as $shift) {
            // if specific event types are set
            if ($calendarAbo->specific_event_types) {
                if (!in_array($shift->event()->first()->event_type_id, $calendarAbo->event_types, true)) {
                    continue;
                }
            }

            $calendar->event(function ($event) use ($calendarAbo, $shift): void {
                $shiftStart = Carbon::parse($shift->start_date)->format('Y-m-d');
                $shiftEnd = Carbon::parse($shift->end_date)->format('Y-m-d');
                $shiftEvent = $shift->event()->first();
                $event
                    ->name('Schicht: ' . $shift->craft->first()->name . ' - ' . $shift->start . ' - ' . $shift->end)
                    ->description('Schicht: ' . $shift->craft->first()->name .
                        ' - ' . $shift->start . ' - ' . $shift->end . ' Projekt: ' . $shiftEvent->project()->first()->name . ' Event: ' . $shiftEvent->eventName ?? '')
                    ->startsAt(Carbon::parse($shiftStart . ' ' . $shift->start))
                    ->endsAt(Carbon::parse($shiftEnd . ' ' . $shift->end))
                    ->organizer($shiftEvent->creator()->first()?->email, $shiftEvent->creator()->first()?->full_name);
                if ($calendarAbo->enable_notification) {
                    $alertTime = Carbon::now();
                    if ($calendarAbo->notification_time_unit === 'minutes') {
                        $alertTime->subMinutes($calendarAbo->notification_time);
                    } elseif ($calendarAbo->notification_time_unit === 'hours') {
                        $alertTime->subHours($calendarAbo->notification_time);
                    } elseif ($calendarAbo->notification_time_unit === 'days') {
                        $alertTime->subDays($calendarAbo->notification_time);
                    }
                    $event->alertAt(
                        $alertTime,
                        'Schicht: ' . $shift->craft->first()->name . ' - ' . $shift->start . ' - ' . $shift->end
                    );

                    $shiftUsers = $shift->users;
                    foreach ($shiftUsers as $shiftUser) {
                        $event->attendee($shiftUser->email, $shiftUser->full_name);
                    }

                    $shiftFreelancer = $shift->freelancers;
                    foreach ($shiftFreelancer as $freelancer) {
                        $event->attendee($freelancer->email, $freelancer->full_name);
                    }
                }
            });
        }


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
        $userShiftCalendarAbo->update($request->only([
            'date_range',
            'start_date',
            'end_date',
            'specific_event_types',
            'event_types',
            'enable_notification',
            'notification_time',
            'notification_time_unit',
        ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserShiftCalendarAbo $userShiftCalendarAbo): void
    {
        //
    }
}
