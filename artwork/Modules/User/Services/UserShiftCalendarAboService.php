<?php

namespace Artwork\Modules\User\Services;

use App\Settings\ShiftSettings;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Artwork\Modules\User\Repositories\UserShiftCalendarAboRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

readonly class UserShiftCalendarAboService
{
    public function __construct(
        private UserShiftCalendarAboRepository $userShiftCalendarAboRepository,
        private ShiftSettings $shiftSettings,
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
        // Only filter for committed shifts if the global setting is not set to show all shifts
        if (!$this->shiftSettings->calendar_abo_show_all_shifts) {
            $shifts = $shifts->where('is_committed', true);
        }

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
            // Individuelle Zuweisungszeiten (shift_workers-Pivot) haben Vorrang
            // vor den allgemeinen Schichtzeiten — gleiche Semantik wie im
            // WorkingHourService
            $pivot = $shift->pivot ?? null;
            $startTime = $pivot?->start_time ?? $shift->start;
            $endTime = $pivot?->end_time ?? $shift->end;
            $shiftStart = Carbon::parse($pivot?->start_date ?? $shift->start_date)->format('Y-m-d');
            $shiftEnd = Carbon::parse($pivot?->end_date ?? $shift->end_date)->format('Y-m-d');
            $shiftEvent = $shift->event;
            $eventCreator = $shiftEvent?->creator;
            $craftName = $shift->craft?->name ?? 'Unbekannte Tätigkeit';
            // Eventlose Schichten tragen Projekt/Raum direkt auf der Schicht
            $projectName = $shift->project?->name ?? $shiftEvent?->project?->name ?? '';
            $eventName = $shiftEvent?->eventName ?? '';
            $roomName = $shift->room?->name ?? $shiftEvent?->room?->name ?? '';

            $title = 'Schicht: ' . $craftName;
            if (!empty($startTime) && !empty($endTime)) {
                $title .= ' - ' . $startTime . ' - ' . $endTime;
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
                $startTime,
                $endTime,
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
                    ->startsAt(Carbon::parse($shiftStart . ' ' . $startTime))
                    ->endsAt(Carbon::parse($shiftEnd . ' ' . $endTime))
                    ->uniqueIdentifier('shift-' . $shift->id)
                    ->createdAt(Carbon::parse($shiftEvent->created_at ?? $shift->created_at));

                if ($eventCreator) {
                    $event->organizer($eventCreator->email, $eventCreator->full_name);
                }

                $this->addAttendeesToEvent($event, $shift, $eventCreator);
                $this->addAlertToEvent($event, $calendarAbo, $shiftStart, $startTime, $shift, $endTime);
            });
        } catch (\Throwable $e) {
            // Skip invalid shifts silently
        }
    }

    /**
     * Get filtered individual times based on the calendar abo date range
     */
    public function getFilteredIndividualTimes($calendarAbo, $individualTimes)
    {
        if ($calendarAbo->date_range) {
            $rangeStart = Carbon::parse($calendarAbo->start_date);
            $rangeEnd = Carbon::parse($calendarAbo->end_date);
            $individualTimes = $individualTimes->filter(
                function ($individualTime) use ($rangeStart, $rangeEnd) {
                    return Carbon::parse($individualTime->start_date)->between($rangeStart, $rangeEnd)
                        && Carbon::parse($individualTime->end_date)->between($rangeStart, $rangeEnd);
                }
            );
        }
        return $individualTimes->sortBy('start_date');
    }

    /**
     * Add an individual time entry to the calendar
     */
    public function addIndividualTimeToCalendar($calendar, $calendarAbo, $individualTime): void
    {
        try {
            $title = trim((string) $individualTime->title);
            if ($title === '') {
                $title = 'Individuelle Zeit';
            }

            $calendar->event(function ($event) use ($calendarAbo, $individualTime, $title): void {
                $event->name($title)
                    ->description($title)
                    ->uniqueIdentifier('individual-time-' . $individualTime->id)
                    ->createdAt(Carbon::parse($individualTime->created_at ?? Carbon::now()));

                $hasTimes = !empty($individualTime->start_time) && !empty($individualTime->end_time);
                if ($individualTime->full_day || !$hasTimes) {
                    // fullDay() muss vor endsAt() stehen: erst dann rechnet die
                    // Bibliothek das exklusive DTEND (+1 Tag) selbst
                    $event->fullDay()
                        ->startsAt(Carbon::parse($individualTime->start_date))
                        ->endsAt(Carbon::parse($individualTime->end_date));
                } else {
                    $event
                        ->startsAt(Carbon::parse(
                            $individualTime->start_date . ' ' . $individualTime->start_time
                        ))
                        ->endsAt(Carbon::parse(
                            $individualTime->end_date . ' ' . $individualTime->end_time
                        ));
                }

                if ($calendarAbo->enable_notification && $hasTimes && !$individualTime->full_day) {
                    $this->addAlertToIndividualTimeEvent($event, $calendarAbo, $individualTime, $title);
                }
            });
        } catch (\Throwable $e) {
            // Skip invalid entries silently
        }
    }

    /**
     * Add alert to an individual time event
     */
    private function addAlertToIndividualTimeEvent($event, $calendarAbo, $individualTime, string $title): void
    {
        $alertTime = Carbon::parse($individualTime->start_date . ' ' . $individualTime->start_time);
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
        $event->alertAt($alertTime, $title . ' beginnt in ' .
            $calendarAbo->notification_time . ' ' . $calendarAbo->notification_time_unit);
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
    public function addAlertToEvent($event, $calendarAbo, $shiftStart, $shiftStartTime, $shift, $shiftEndTime = null): void
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
            $craftName = $shift->craft?->name ?? 'Schicht';
            $event->alertAt($alertTime, 'Schicht: ' . $craftName . ' - ' .
                $shiftStartTime . ' - ' . ($shiftEndTime ?? $shift->end) . ' beginnt in ' .
                $calendarAbo->notification_time . ' ' . $calendarAbo->notification_time_unit);
        }
    }
}
