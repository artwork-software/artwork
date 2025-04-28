<?php

namespace Artwork\Modules\Calendar\DTO;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\SeriesEvents\Models\SeriesEvents;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;

class EventDTO extends Data
{
    public function __construct(
        public int $id,
        public string $start,
        public string $end,
        public ?string $eventName,
        public ?string $description,
        public ?ProjectDTO $project,
        public EventType|null $eventType,
        public ?array $shifts,
        public bool $allDay,
        public ?int $roomId,
        public ?string $roomName,
        public ?array $daysOfEvent,
        public ?int $startHour,
        public ?int $minutesFormStartHourToStart,
        public ?int $eventLengthInHours,
        public User|null|Optional $created_by,
        public ?array $formattedDates,
        public ?bool $is_series,
        public ?int $series_id,
        public Collection $eventProperties,
        public ?bool $occupancy_option,
        public ?int $declinedRoomId = null,
        public Lazy|EventStatus|null $eventStatus,
        public Collection $subEvents,
        public SeriesEvents|null $series,
        public ?string $option_string,
        public ?bool $isPlanning = false,
        public ?bool $hasVerification = false,
    ) {
    }

    public static function fromModel(
        Event $event,
        UserCalendarSettings $userCalendarSettings,
        Collection $projects,
        Collection $eventTypes,
        Collection $users,
        Collection $eventStatuses,
    ): EventDTO {
        return new self(
            id: $event->id,
            start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
            end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
            eventName: $event->eventName,
            description: $event->description,
            project: $event->project_id ? ProjectDTO::fromModel($projects[$event->project_id], $userCalendarSettings) : null,
            eventType: $eventTypes[$event->event_type_id] ?? null,
            shifts: $userCalendarSettings->work_shifts ?
                MinimalShiftPlanShiftResource::collection($event->shifts)->resolve() :
                null,
            allDay: $event->allDay,
            roomId: $event->room_id,
            roomName: $event->room->name,
            daysOfEvent: $event->getAttribute('days_of_event') ?? [],
            startHour: $event->getAttribute('start_hour') ?? 0,
            minutesFormStartHourToStart: $event->getAttribute('minutes_form_start_hour_to_start') ?? 0,
            eventLengthInHours: $event->getAttribute('event_length_in_hours') ?? 0,
            created_by: $event->user_id ? $users[$event->user_id] : null,
            formattedDates: $event->getAttribute('formatted_dates') ?? [],
            is_series: $event->is_series,
            series_id: $event->series_id,
            eventProperties: $event->eventProperties,
            occupancy_option: $event->occupancy_option,
            declinedRoomId: $event->declined_room_id,
            eventStatus: $userCalendarSettings->use_event_status_color && $event?->event_type_id !== null
                ? ($eventStatuses[$event->event_status_id] ?? null)
                : ($event?->event_type_id !== null
                    ? Lazy::inertia(fn() => $eventStatuses[$event->event_status_id] ?? null)
                    : null
                ),
            subEvents: $event->subEvents,
            series: $event->is_series ? $event->series : null,
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: $event->getAttribute('has_verification') ?? false,
        );
    }
}
