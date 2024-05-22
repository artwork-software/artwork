<?php

namespace App\Http\Controllers;

use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Artwork\Modules\UserShiftCalendarAbo\Services\UserShiftCalendarAboService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $this->userShiftCalendarAboService->create($request->all());
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
        $shifts = $this->getFilteredShifts($calendarAbo, $user->shifts);

        // Process each shift and add events to the calendar
        foreach ($shifts as $shift) {
            $shiftEvent = $shift->event()->first();
            if ($this->shouldAddShiftEvent($calendarAbo, $shiftEvent)) {
                $this->addEventToCalendar($calendar, $calendarAbo, $shift, $shiftEvent);
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
     * Get filtered shifts based on the calendar abo settings
     */
    private function getFilteredShifts($calendarAbo, $shifts)
    {
        if ($calendarAbo->date_range) {
            $shifts = $shifts->whereBetween('start_date', [$calendarAbo->start_date, $calendarAbo->end_date])
                ->whereBetween('end_date', [$calendarAbo->start_date, $calendarAbo->end_date]);
        }
        return $shifts->sortBy('start_date');
    }

    /**
     * Determine if the shift event should be added to the calendar
     */
    private function shouldAddShiftEvent($calendarAbo, $shiftEvent)
    {
        if ($calendarAbo->specific_event_types) {
            return in_array($shiftEvent->event_type_id, $calendarAbo->event_types, true);
        }
        return true;
    }

    /**
     * Add an event to the calendar
     */
    private function addEventToCalendar($calendar, $calendarAbo, $shift, $shiftEvent): void
    {
        $shiftStart = Carbon::parse($shift->start_date)->format('Y-m-d');
        $shiftEnd = Carbon::parse($shift->end_date)->format('Y-m-d');
        $eventCreator = $shiftEvent->creator()->first();
        $craft = $shift->craft()->first();
        $projectName = $shiftEvent->project()->first()->name;
        $eventName = $shiftEvent->eventName ?? '';

        $calendar->event(function ($event) use (
            $shiftEvent,
            $calendarAbo,
            $shift,
            $shiftStart,
            $shiftEnd,
            $eventCreator,
            $craft,
            $projectName,
            $eventName
        ): void {
            $event->name('Schicht: ' . $craft->name . ' - ' . $shift->start . ' - ' . $shift->end)
                ->description(
                    'Schicht: ' . $craft->name . ' - ' . $shift->start . ' - ' . $shift->end .
                    ' Projekt: ' . $projectName .
                    ' Event: ' . $eventName .
                    ' Raum: ' . $shiftEvent->room()->first()->name
                )
                ->address('Raum: ' . $shiftEvent->room()->first()->name . ' | Event: ' . $eventName)
                ->startsAt(Carbon::parse($shiftStart . ' ' . $shift->start))
                ->endsAt(Carbon::parse($shiftEnd . ' ' . $shift->end))
                ->organizer($eventCreator->email, $eventCreator->full_name);
            $this->addAttendeesToEvent($event, $shift, $eventCreator);
            $this->addAlertToEvent($event, $calendarAbo, $shiftStart, $shift->start, $shift);
        });
    }

    /**
     * Add attendees to the event
     */
    private function addAttendeesToEvent($event, $shift, $eventCreator): void
    {
        foreach ($shift->users as $shiftUser) {
            if ($eventCreator->id !== $shiftUser->id) {
                $event->attendee($shiftUser->email, $shiftUser->full_name . ' (Intern)');
            }
        }

        foreach ($shift->freelancer as $freelancer) {
            $event->attendee($freelancer->email, $freelancer->name . ' (Extern)');
        }

        foreach ($shift->serviceProvider as $provider) {
            $event->attendee($provider->email, $provider->provider_name . ' (Dienstleister)');
        }
    }

    /**
     * Add alert to the event if notifications are enabled
     */
    private function addAlertToEvent($event, $calendarAbo, $shiftStart, $shiftStartTime, $shift): void
    {
        if ($calendarAbo->enable_notification) {
            $alertTime = Carbon::parse($shiftStart . ' ' . $shiftStartTime);
            switch ($calendarAbo->notification_time_unit) {
                case 'minutes':
                    $alertTime->subMinutes($calendarAbo->notification_time);
                    break;
                case 'hours':
                    $alertTime->subHours($calendarAbo->notification_time);
                    break;
                case 'days':
                    $alertTime->subDays($calendarAbo->notification_time);
                    break;
            }
            $event->alertAt($alertTime, 'Schicht: ');
        }
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
