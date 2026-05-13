<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Modules\Event\Models\Event;
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
        public ?int $projectId,
        public ?int $eventTypeId,
        public ?Collection $shifts,
        public bool $allDay,
        public ?int $roomId,
        public ?string $roomName,
        public MinimalCreatorDTO|Optional|null $created_by,
        public ?bool $is_series,
        public ?array $eventProperties,
        public ?bool $occupancy_option,
        public ?string $option_string,
        public ?bool $isPlanning,
        public ?bool $hasVerification = false,
        public ?bool $hasTimelines = false,
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
            projectId: $project?->id,
            eventTypeId: $event->event_type_id,
            shifts: collect([]),
            allDay: $event->allDay,
            roomId: $event->room_id,
            roomName: $event->room?->name,
            created_by: $event->creator ? new MinimalCreatorDTO(
                id: $event->creator->id,
                first_name: $event->creator->first_name,
                last_name: $event->creator->last_name,
                profile_photo_url: $event->creator->getAttribute('profile_photo_url'),
            ) : null,
            is_series: $event->is_series,
            eventProperties: self::serializeEventProperties($event),
            occupancy_option: $event->occupancy_option,
            option_string: $event->option_string,
            isPlanning: $event->is_planning ?? false,
            hasVerification: false,
            hasTimelines: (bool) $event->timelines_exists,
            timelines: $addTimeline ? ($event->getAttribute('timelines') ?? collect()) : collect(),
        );
    }

    private static function serializeEventProperties(Event $event): array
    {
        if (!$event->relationLoaded('eventProperties')) {
            return [];
        }

        return $event->eventProperties->map(fn ($prop) => [
            'id' => $prop->id,
            'name' => $prop->name,
            'icon' => $prop->icon,
        ])->values()->all();
    }
}
