<?php

namespace Artwork\Modules\UserCalendarAbo\Services;

use Artwork\Modules\UserCalendarAbo\Models\UserCalendarAbo;
use Artwork\Modules\UserCalendarAbo\Repositories\UserCalendarAboRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\IcalendarGenerator\Enums\EventStatus;

readonly class UserCalendarAboService
{
    public function __construct(
        private UserCalendarAboRepository $userCalendarAboRepository
    ) {
    }

    public function create(array $data, int $userId): void
    {
        $calendarAbo = new UserCalendarAbo();
        $calendarAbo->user_id = $userId;
        $calendarAbo->calendar_abo_id = $data['calendar_abo_id'] ?? Str::uuid();
        $calendarAbo->date_range = $data['date_range'];
        $calendarAbo->start_date = $data['start_date'];
        $calendarAbo->end_date = $data['end_date'];
        $calendarAbo->specific_event_types = $data['specific_event_types'];
        $calendarAbo->event_types = $data['event_types'];
        $calendarAbo->specific_rooms = $data['specific_rooms'];
        $calendarAbo->selected_rooms = $data['selected_rooms'];
        $calendarAbo->specific_areas = $data['specific_areas'];
        $calendarAbo->selected_areas = $data['selected_areas'];
        $calendarAbo->enable_notification = $data['enable_notification'];
        $calendarAbo->notification_time = $data['notification_time'];
        $calendarAbo->notification_time_unit = $data['notification_time_unit'];
        $this->userCalendarAboRepository->save($calendarAbo);
    }


    public function updateByRequest(array $data, UserCalendarAbo $calendarAbo): void
    {
        $calendarAbo->fill($data);
        $this->userCalendarAboRepository->save($calendarAbo);
    }

    public function getFilteredEvents($calendarAbo, $events)
    {
        if ($calendarAbo->date_range) {
            $events = $events->whereBetween('start_date', [$calendarAbo->start_date, $calendarAbo->end_date])
                ->whereBetween('end_date', [$calendarAbo->start_date, $calendarAbo->end_date]);
        }
        if ($calendarAbo->specific_event_types) {
            $events = $events->whereIn('event_type_id', $calendarAbo->event_types);
        }

        if ($calendarAbo->specific_rooms) {
            $events = $events->whereIn('room_id', $calendarAbo->selected_rooms);
        }

        return $events->sortBy('start_date');
    }

    public function addEventToCalendar($calendar, $event): void
    {
        $calendar->event(function ($calendarEvent) use ($event): void {
            $calendarEvent
                ->name($event->name ?? $event->eventName . ' - ' . $event->project->name)
                ->description($event->description ?? '')
                ->uniqueIdentifier($event->id)
                ->createdAt(Carbon::parse($event->created_at))
                ->startsAt(Carbon::parse($event->start_date))
                ->endsAt(Carbon::parse($event->end_date));


            if ($event->room && $event->project) {
                $calendarEvent->address('Raum: ' . $event->room->name . ' | Projekt: ' . $event->project->name);
            } elseif ($event->room) {
                $calendarEvent->address('Raum: ' . $event->room->name);
            } elseif ($event->project) {
                $calendarEvent->address('Projekt: ' . $event->project->name);
            }

            if ($event->room === null) {
                $calendarEvent->status(EventStatus::cancelled());
            }

            if ($event->creator) {
                $calendarEvent->organizer($event->creator->email, $event->creator->full_name);
            }

            if ($event->project) {
                $calendarEvent->url(
                    route(
                        'projects.tab',
                        [
                        'project' => $event->project->id,
                        'projectTab' => 1]
                    ),
                    'Im Projekt anzeigen'
                );
            }
        });
    }
}
