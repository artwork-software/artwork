<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
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
        public MinimalCreatorDTO|Optional|null $created_by,
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
        ?Project $project,
        ?bool $addTimeline = false
    ): EventShiftPlanDTO {
        if ($event->allDay) {
            $startTime = Carbon::parse($event->start_time)->format('Y-m-d') . ' 00:00';
            $endTime   = Carbon::parse($event->end_time)->format('Y-m-d') . ' 23:59';
        } else {
            $startTime = Carbon::parse($event->start_time)->format('Y-m-d H:i');
            $endTime   = Carbon::parse($event->end_time)->format('Y-m-d H:i');
        }

        return new self(
            id: $event->id,
            start: $startTime,
            end: $endTime,
            eventName: $event->eventName,
            description: $event->description,
            project: $project ? ProjectDTO::fromModelForCalendar($project) : null, // wichtig!
            eventType: $event->event_type,
            shifts: collect([]),
            allDay: $event->allDay,
            roomId: $event->room_id,
            roomName: $event->room?->name,
            daysOfEvent: $event->getAttribute('days_of_event') ?? [],
            created_by: $event->creator ? new MinimalCreatorDTO(
                id: $event->creator->id,
                first_name: $event->creator->first_name,
                last_name: $event->creator->last_name,
                profile_photo_url: $event->creator->getAttribute('profile_photo_url'),
            ) : null,
            formattedDates: $event->getAttribute('formatted_dates') ?? [],
            is_series: $event->is_series,
            eventProperties: $event->eventProperties,
            occupancy_option: $event->occupancy_option,
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: false,
            timelines: $addTimeline ? ($event->getAttribute('timelines') ?? collect()) : collect(),
        );
    }
}
