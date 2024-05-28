<?php

namespace Artwork\Modules\UserShiftCalendarAbo\Services;

use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Artwork\Modules\UserShiftCalendarAbo\Repositories\UserShiftCalendarAboRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

readonly class UserShiftCalendarAboService
{
    public function __construct(
        private UserShiftCalendarAboRepository $userShiftCalendarAboRepository
    ) {
        //
    }

    public function create(array $data, int $userId): void
    {
        $calendarAbo = new UserShiftCalendarAbo();
        $calendarAbo->user_id = $userId;
        // $calendarAbo->calendar_abo_id random string
        $calendarAbo->calendar_abo_id = $data['calendar_abo_id'] ?? Str::uuid();
        $calendarAbo->date_range = $data['date_range'];
        $calendarAbo->start_date = $data['start_date'];
        $calendarAbo->end_date = $data['end_date'];
        $calendarAbo->specific_event_types = $data['specific_event_types'];
        $calendarAbo->event_types = $data['event_types'];
        $calendarAbo->enable_notification = $data['enable_notification'];
        $calendarAbo->notification_time = $data['notification_time'];
        $calendarAbo->notification_time_unit = $data['notification_time_unit'];

        $this->userShiftCalendarAboRepository->save($calendarAbo);
    }

    public function updateByRequest(
        UserShiftCalendarAbo $calendarAbo,
        array $data
    ): \Artwork\Core\Database\Models\Model {
        $calendarAbo->fill($data);
        return $this->userShiftCalendarAboRepository->save($calendarAbo);
    }

    /**
     * Get filtered shifts based on the calendar abo settings
     */
    public function getFilteredShifts($calendarAbo, $shifts)
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
    public function shouldAddShiftEvent($calendarAbo, $shiftEvent)
    {
        if ($calendarAbo->specific_event_types) {
            return in_array($shiftEvent->event_type_id, $calendarAbo->event_types, true);
        }
        return true;
    }

    /**
     * Add an event to the calendar
     */
    public function addEventToCalendar($calendar, $calendarAbo, $shift, $shiftEvent): void
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
                ->organizer($eventCreator->email, $eventCreator->full_name)
                ->uniqueIdentifier($shiftEvent->id)
                ->createdAt(Carbon::parse($shiftEvent->created_at));
            $this->addAttendeesToEvent($event, $shift, $eventCreator);
            $this->addAlertToEvent($event, $calendarAbo, $shiftStart, $shift->start, $shift);
        });
    }

    /**
     * Add attendees to the event
     */
    public function addAttendeesToEvent($event, $shift, $eventCreator): void
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
    public function addAlertToEvent($event, $calendarAbo, $shiftStart, $shiftStartTime, $shift): void
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
            $event->alertAt($alertTime, 'Schicht: ' . $shift->craft()->first()->name . ' - ' .
                $shift->start . ' - ' . $shift->end . ' beginnt in ' .
                $calendarAbo->notification_time . ' ' . $calendarAbo->notification_time_unit);
        }
    }
}
