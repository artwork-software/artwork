<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\UserCalendarAbo;
use Artwork\Modules\User\Repositories\UserCalendarAboRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
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

    public function getFilteredEventsQuery(UserCalendarAbo $calendarAbo): Builder
    {
        $q = Event::query()
            ->with(['room', 'project', 'creator'])
            ->orderBy('start_time');

        if ($calendarAbo->date_range) {
            $start = Carbon::parse($calendarAbo->start_date)->startOfDay();
            $end = Carbon::parse($calendarAbo->end_date)->endOfDay();

            $q->where(function ($qq) use ($start, $end) {
                $qq->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($qqq) use ($start, $end) {
                        $qqq->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                    });
            });
        }

        if ($calendarAbo->specific_event_types) {
            $q->whereIn('event_type_id', $calendarAbo->event_types ?? []);
        }

        if ($calendarAbo->specific_rooms) {
            $q->whereIn('room_id', $calendarAbo->selected_rooms ?? []);
        }

        if ($calendarAbo->specific_areas) {
            $q->whereHas('room', fn ($r) => $r->whereIn('area_id', $calendarAbo->selected_areas ?? []));
        }

        return $q;
    }

    private function icsTitleFor($item): string
    {
        $candidates = [
            data_get($item, 'title'),
            data_get($item, 'name'),
            data_get($item, 'eventName'),
            data_get($item, 'event.title'),
            data_get($item, 'event.name'),
            data_get($item, 'project.name'),
            data_get($item, 'eventType.name'),
        ];

        foreach ($candidates as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
        }

        Log::warning('ICS: Event without title - using fallback', [
            'class' => is_object($item) ? get_class($item) : gettype($item),
            'id'    => data_get($item, 'id'),
        ]);

        return 'Termin (ohne Titel)';
    }

    public function addEventToCalendar($calendar, $event): void
    {
        try {
            $title = $this->icsTitleFor($event);

            $calendar->event(function ($calendarEvent) use ($event, $title): void {
                $calendarEvent
                    ->name($title)
                    ->description($event->description ?? '')
                    ->uniqueIdentifier($event->id)
                    ->createdAt(Carbon::parse($event->created_at))
                    ->startsAt(Carbon::parse($event->start_time))
                    ->endsAt(Carbon::parse($event->end_time));

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
                                'projectTab' => 1
                            ]
                        ),
                        'Im Projekt anzeigen'
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::error('ICS: Skipping invalid event', [
                'id' => data_get($event, 'id'),
                'class' => is_object($event) ? get_class($event) : gettype($event),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
