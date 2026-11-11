<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Doppelbelegung (S6): zwei Termine im selben Raum, `start`/`end` = Ueberlappung.
 */
class AiRoomConflictData extends Data
{
    /** @param AiConflictEventData[] $events */
    public function __construct(
        public AiRefData $room,
        public string $start,
        public string $end,
        public array $events,
    ) {
    }
}
