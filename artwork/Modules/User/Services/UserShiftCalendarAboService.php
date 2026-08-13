<?php

namespace Artwork\Modules\User\Services;

use App\Settings\ShiftSettings;
use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Artwork\Modules\User\Repositories\UserShiftCalendarAboRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

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
     * @param Collection<int, object> $individualTimes
     * @return Collection<int, object>
     */
    public function getFilteredIndividualTimes(
        UserShiftCalendarAbo $calendarAbo,
        Collection $individualTimes
    ): Collection {
        if ($calendarAbo->date_range) {
            $rangeStart = Carbon::parse($calendarAbo->start_date)->startOfDay();
            $rangeEnd = Carbon::parse($calendarAbo->end_date)->endOfDay();

            $individualTimes = $individualTimes->filter(
                function (object $individualTime) use ($rangeStart, $rangeEnd): bool {
                    $start = Carbon::parse($individualTime->start_date)->startOfDay();
                    $end = Carbon::parse($individualTime->end_date ?? $individualTime->start_date)->endOfDay();

                    return $start->lte($rangeEnd) && $end->gte($rangeStart);
                }
            );
        }

        return $individualTimes
            ->sortBy(fn (object $individualTime): string => sprintf(
                '%s %s',
                $individualTime->start_date,
                $individualTime->start_time ?? '00:00'
            ))
            ->values();
    }

    public function addDayServiceToCalendar(
        Calendar $calendar,
        UserShiftCalendarAbo $calendarAbo,
        DayService $dayService
    ): void {
        try {
            $title = 'Tagesdienst: ' . $dayService->name;
            $date = Carbon::parse($dayService->pivot->date);

            $calendar->event(function (Event $event) use (
                $calendarAbo,
                $dayService,
                $title,
                $date
            ): void {
                $event->name($title)
                    ->description($title)
                    ->uniqueIdentifier('day-service-' . $dayService->pivot->id)
                    ->createdAt(Carbon::parse($dayService->pivot->created_at ?? $dayService->created_at))
                    ->fullDay()
                    // Kopien übergeben: endsAt() mutiert die Instanz bei fullDay via modify('+1 day')
                    ->startsAt($date->copy())
                    ->endsAt($date->copy());

                $this->addStartAlertToEvent($event, $calendarAbo, $date->copy()->startOfDay(), $title);
            });
        } catch (\Throwable $exception) {
            report($exception);
        }
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

            // Titel: Gewerk-Kürzel + Projekt-/Eventname (z.B. "LX - La Horde");
            // keine Uhrzeit, die steckt bereits im Termin selbst
            $craftAbbreviation = trim((string) ($shift->craft?->abbreviation ?? ''));
            $craftLabel = $craftAbbreviation !== '' ? $craftAbbreviation : $craftName;
            $contextName = $projectName !== '' ? $projectName : $eventName;
            $title = $contextName !== ''
                ? $craftLabel . ' - ' . $contextName
                : 'Schicht: ' . $craftLabel;

            // Kolleg*innen der Schicht für die Beschreibung ("Mit: …");
            // die Abo-Inhaber*in selbst wird nicht mit aufgezählt
            $aboUserId = $calendarAbo->user_id;
            $colleagueNames = $shift->users
                ->reject(fn ($shiftUser) => $aboUserId !== null && $shiftUser->id === $aboUserId)
                ->map(fn ($shiftUser) => $shiftUser->full_name)
                ->concat($shift->freelancer->pluck('name'))
                ->concat($shift->serviceProvider->pluck('provider_name'))
                ->filter()
                ->implode(', ');

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
                $roomName,
                $colleagueNames
            ): void {
                $event->name($title)
                    ->description(
                        'Schicht: ' . $craftName .
                        ($projectName !== '' ? ' Projekt: ' . $projectName : '') .
                        ($eventName !== '' ? ' Event: ' . $eventName : '') .
                        ($roomName !== '' ? ' Raum: ' . $roomName : '') .
                        ($colleagueNames !== '' ? ' Mit: ' . $colleagueNames : '')
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
                $this->addAlertToEvent($event, $calendarAbo, $shiftStart, $startTime, $shift, $endTime, $title);
            });
        } catch (\Throwable $e) {
            return;
        }
    }

    public function addIndividualTimeToCalendar(
        $calendar,
        UserShiftCalendarAbo $calendarAbo,
        IndividualTime $individualTime
    ): void {
        try {
            $title = trim((string) $individualTime->title);
            $title = $title !== '' ? 'Individuelle Zeit: ' . $title : 'Individuelle Zeit';
            $startDate = Carbon::parse($individualTime->start_date);
            $endDate = Carbon::parse($individualTime->end_date ?? $individualTime->start_date);

            $calendar->event(function ($event) use (
                $calendarAbo,
                $individualTime,
                $title,
                $startDate,
                $endDate
            ): void {
                $event->name($title)
                    ->description($title)
                    ->uniqueIdentifier('individual-time-' . $individualTime->id)
                    ->createdAt(Carbon::parse($individualTime->created_at));

                if ($individualTime->full_day || !$individualTime->start_time || !$individualTime->end_time) {
                    $event->fullDay()
                        ->startsAt($startDate)
                        ->endsAt($endDate);

                    $eventStart = $startDate->copy()->startOfDay();
                } else {
                    $eventStart = Carbon::parse($startDate->toDateString() . ' ' . $individualTime->start_time);
                    $eventEnd = Carbon::parse($endDate->toDateString() . ' ' . $individualTime->end_time);

                    if ($eventEnd->lt($eventStart)) {
                        $eventEnd->addDay();
                    }

                    $event->startsAt($eventStart)->endsAt($eventEnd);
                }

                $this->addStartAlertToEvent($event, $calendarAbo, $eventStart, $title);
            });
        } catch (\Throwable $e) {
            return;
        }
    }

    private function addStartAlertToEvent(
        $event,
        UserShiftCalendarAbo $calendarAbo,
        Carbon $eventStart,
        string $title
    ): void {
        if (!$calendarAbo->enable_notification) {
            return;
        }

        $alertTime = $eventStart->copy();

        match ($calendarAbo->notification_time_unit) {
            'minutes' => $alertTime->subMinutes($calendarAbo->notification_time),
            'hours' => $alertTime->subHours($calendarAbo->notification_time),
            'days' => $alertTime->subDays($calendarAbo->notification_time),
            default => null,
        };

        $event->alertAt(
            $alertTime,
            $title . ' beginnt in ' . $calendarAbo->notification_time . ' ' .
            $calendarAbo->notification_time_unit
        );
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
    public function addAlertToEvent(
        $event,
        $calendarAbo,
        $shiftStart,
        $shiftStartTime,
        $shift,
        $shiftEndTime = null,
        ?string $title = null
    ): void {
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
            $alertTitle = $title ?? ('Schicht: ' . ($shift->craft?->name ?? 'Schicht'));
            $event->alertAt($alertTime, $alertTitle . ' (' .
                $shiftStartTime . ' - ' . ($shiftEndTime ?? $shift->end) . ') beginnt in ' .
                $calendarAbo->notification_time . ' ' . $calendarAbo->notification_time_unit);
        }
    }
}
