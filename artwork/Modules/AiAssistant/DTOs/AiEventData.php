<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Event\Models\Event;
use Spatie\LaravelData\Data;

/**
 * Ein Termin aus GET /api/v1/ai/events (Konzept B1 §3).
 */
class AiEventData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $room,
        public ?AiRefData $project,
        public ?AiRefData $type,
        public ?string $status,
    ) {
    }

    public static function fromModel(Event $event): self
    {
        $timezone = (string) config('app.timezone');

        return new self(
            id: $event->id,
            name: AiText::truncate($event->eventName ?: $event->name),
            start: $event->start_time?->copy()->setTimezone($timezone)->toIso8601String(),
            end: $event->end_time?->copy()->setTimezone($timezone)->toIso8601String(),
            room: AiRefData::make($event->room_id, $event->room?->name),
            project: AiRefData::make($event->project_id, $event->project?->name),
            type: AiRefData::make($event->event_type_id, $event->event_type?->name),
            status: AiText::truncate($event->eventStatus?->name),
        );
    }
}
