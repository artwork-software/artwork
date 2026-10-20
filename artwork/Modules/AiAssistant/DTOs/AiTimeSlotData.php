<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein freies Zeitfenster (ISO 8601, Haus-Zeitzone).
 */
class AiTimeSlotData extends Data
{
    public function __construct(
        public string $start,
        public string $end,
    ) {
    }
}
