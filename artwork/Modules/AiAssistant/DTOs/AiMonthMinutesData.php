<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Minuten eines Kalendermonats (`YYYY-MM`).
 */
class AiMonthMinutesData extends Data
{
    public function __construct(
        public string $month,
        public int $minutes,
    ) {
    }
}
