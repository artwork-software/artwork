<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Core\Dto\TransformsWithoutDataPipeline;
use Spatie\LaravelData\Data;

class CalendarRoomDTO extends Data
{
    use TransformsWithoutDataPipeline;

    public function __construct(
        public int $roomId,
        public string $roomName,
        public ?array $content,
        public ?array $eventsById = null,
        public ?array $shiftsById = null,
        public ?string $roomColor = null,
    ) {
    }
}
