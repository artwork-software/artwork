<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiRoomOccupancyData extends Data
{
    /** @param AiOccupancyDayData[] $days */
    public function __construct(
        public AiRefData $room,
        public array $days,
    ) {
    }
}
