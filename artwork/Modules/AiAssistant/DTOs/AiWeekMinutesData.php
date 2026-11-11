<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Minuten einer ISO-Woche (`YYYY-Www`).
 */
class AiWeekMinutesData extends Data
{
    public function __construct(
        public string $week,
        public int $minutes,
    ) {
    }
}
