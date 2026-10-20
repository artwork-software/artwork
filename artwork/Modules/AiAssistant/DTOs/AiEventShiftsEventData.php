<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiEventShiftsEventData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $room,
        public ?AiRefData $project,
    ) {
    }
}
