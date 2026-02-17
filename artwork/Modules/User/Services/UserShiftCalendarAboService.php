<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Artwork\Modules\User\Repositories\UserShiftCalendarAboRepository;
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
        $calendarAbo->specific_crafts = $data['specific_crafts'];
        $calendarAbo->craft_ids = $data['craft_ids'];
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
        // Always filter for committed shifts only
        $shifts = $shifts->where('is_committed', true);

        if ($calendarAbo->date_range) {
            $shifts = $shifts->filter(function ($shift) use ($calendarAbo) {
                $startDate = Carbon::parse($shift->start_date);
                $endDate = Carbon::parse($shift->end_date);
                $rangeStart = Carbon::parse($calendarAbo->start_date);
                $rangeEnd = Carbon::parse($calendarAbo->end_date);
                return $startDate->between($rangeStart, $rangeEnd)
                    && $endDate->between($rangeStart, $rangeEnd);
            });
        }
        return $shifts->sortBy('start_date');
    }

    /**
     * Determine if the shift should be added to the calendar based on craft filter
     */
    public function shouldAddShift($calendarAbo, $shift): bool
    {
        if ($calendarAbo->specific_crafts) {
            $craftIds = is_array($calendarAbo->craft_ids) ? $calendarAbo->craft_ids : [];
            return in_array($shift->craft_id, $craftIds, true);
        }
        return true;
    }

    /**
     * Add a shift to the calendar
     */
    public function addShiftToCalendar($calendar, $calendarAbo, $shift): void
    {
        try {
            $shiftStart = Carbon::parse($shift->start_date)->format('Y-m-d');
            $shiftEnd = Carbon::parse($shift->end_date)->format('Y-m-d');
            $shiftEvent = method_exists($shift, 'event') ? $shift->event()->first() : null;
            $eventCreator = $shiftEvent?->creator()->first();
            $craft = $shift->craft()->first();
            $craftName = $craft->name ?? 'Unbekannte Tätigkeit';
            $projectName = optional($shiftEvent?->project()->first())->name ?? '';
            $eventName = $shiftEvent?->eventName ?? '';
            $roomName = optional($shiftEvent?->room()->first())->name ?? '';

            $title = 'Schicht: ' . $craftName;
            if (!empty($shift->start) && !empty($shift->end)) {
                $title .= ' - ' . $shift->start . ' - ' . $shift->end;
            }
            if (trim($title) === '') {
                $title = 'Schicht (ohne Titel)';
            }

            $calendar->event(function ($event) use (
                $shiftEvent,
                $calendarAbo,
                $shift,
                $shiftStart,
                $shiftEnd,
                $eventCreator,
                $title,
                $craftName,
                $projectName,
                $eventName,
                $roomName
            ): void {
                $event->name($title)
                    ->description(
                        $title .
                        ($projectName !== '' ? ' Projekt: ' . $projectName : '') .
                        ($eventName !== '' ? ' Event: ' . $eventName : '') .
                        ($roomName !== '' ? ' Raum: ' . $roomName : '')
                    )
                    ->address(
                        ($roomName !== '' ? 'Raum: ' . $roomName : '') .
                        ($eventName !== '' ? ' | Event: ' . $eventName : '')
                    )
                    ->startsAt(Carbon::parse($shiftStart . ' ' . $shift->start))
                    ->endsAt(Carbon::parse($shiftEnd . ' ' . $shift->end))
                    ->uniqueIdentifier('shift-' . $shift->id)
                    ->createdAt(Carbon::parse($shiftEvent->created_at ?? $shift->created_at));

                if ($eventCreator) {
                    $event->organizer($eventCreator->email, $eventCreator->full_name);
                }

                $this->addAttendeesToEvent($event, $shift, $eventCreator);
                $this->addAlertToEvent($event, $calendarAbo, $shiftStart, $shift->start, $shift);
            });
        } catch (\Throwable $e) {
            // Skip invalid shifts silently
        }
    }

    /**
     * Add attendees to the event
     */
    public function addAttendeesToEvent($event, $shift, $eventCreator): void
    {
        foreach ($shift->users as $shiftUser) {
            if (!$eventCreator || $eventCreator->id !== $shiftUser->id) {
                $event->attendee($shiftUser->email, $shiftUser->full_name . ' (Intern)');
            }
        }

        foreach ($shift->freelancer as $freelancer) {
            if (!empty($freelancer->email)) {
                $event->attendee($freelancer->email, $freelancer->name . ' (Extern)');
            }
        }

        foreach ($shift->serviceProvider as $provider) {
            if (!empty($provider->email)) {
                $event->attendee($provider->email, $provider->provider_name . ' (Dienstleister)');
            }
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
            $craftName = optional($shift->craft()->first())->name ?? 'Schicht';
            $event->alertAt($alertTime, 'Schicht: ' . $craftName . ' - ' .
                $shift->start . ' - ' . $shift->end . ' beginnt in ' .
                $calendarAbo->notification_time . ' ' . $calendarAbo->notification_time_unit);
        }
    }
}
