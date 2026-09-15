<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiFreeSlotData extends Data
{
    public function __construct(
        public int $room_id,
        public string $start,
        public string $end,
        public float $hours,
    ) {
    }
}
