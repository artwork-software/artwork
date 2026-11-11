<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\AiAssistant\Support\AiText;
use Spatie\LaravelData\Data;

/**
 * Kurzform eines Termins fuer die Projektdetails (Konzept B1 §3).
 */
class AiEventBriefData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $start,
        public ?string $room,
    ) {
    }

    public static function fromModel(Event $event): self
    {
        $timezone = (string) config('app.timezone');

        return new self(
            id: $event->id,
            name: AiText::truncate($event->eventName ?? $event->name ?? null),
            start: $event->start_time?->copy()->setTimezone($timezone)->toIso8601String(),
            room: AiText::truncate($event->room?->name),
        );
    }
}
