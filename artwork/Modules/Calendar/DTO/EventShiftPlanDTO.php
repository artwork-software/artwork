<?php

namespace Artwork\Modules\Calendar\DTO;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\SeriesEvents\Models\SeriesEvents;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;

class EventShiftPlanDTO extends Data
{
    public function __construct(
        public int $id,
        public string $start,
        public string $end,
        public ?string $eventName,
        public ?string $description,
        public ?ProjectDTO $project,
        public EventType|null $eventType,
        public ?Collection $shifts,
        public bool $allDay,
        public ?int $roomId,
        public ?string $roomName,
        public ?array $daysOfEvent,
        public User|null|Optional $created_by,
        public ?array $formattedDates,
        public ?bool $is_series,
        public Collection $eventProperties,
        public ?bool $occupancy_option,
        public ?string $option_string,
        public ?bool $isPlanning,
        public ?bool $hasVerification = false,
        public ?Collection $timelines = null
    ) {
    }

    public static function fromModel(
        Event $event,
        Collection $eventTypes,
        Collection $users,
        ?bool $addTimeline = false,
    ): EventShiftPlanDTO
    {
        return new self(
            id: $event->id,
            start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
            end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
            eventName: $event->eventName,
            description: $event->description,
            project: $event->project ? ProjectDTO::fromModel($event->project) : null,
            eventType: $eventTypes[$event->event_type_id] ?? null,
            shifts: $event->shifts->map(fn($shift) => ShiftDTO::fromModel($shift)),
            allDay: $event->allDay,
            roomId: $event->room_id,
            roomName: $event->room->name,
            daysOfEvent: $event->getAttribute('days_of_event') ?? [],
            created_by: $event->user_id ? $users[$event->user_id] : null,
            formattedDates: $event->getAttribute('formatted_dates') ?? [],
            is_series: $event->is_series,
            eventProperties: $event->eventProperties,
            occupancy_option: $event->occupancy_option,
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: $event->getAttribute('has_verification') ?? false,
            timelines: $addTimeline ? $event->getAttribute('timelines') : collect([]),
        );
    }
}