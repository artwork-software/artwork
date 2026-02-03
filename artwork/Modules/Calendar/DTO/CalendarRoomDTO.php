<?php

namespace Artwork\Modules\Calendar\DTO;

use Spatie\LaravelData\Data;

class CalendarRoomDTO extends Data
{
    public function __construct(
        public int $roomId,
        public string $roomName,
        public ?array $content,
        public ?array $eventsById = null,
        public ?array $shiftsById = null,
    ) {
    }
}
