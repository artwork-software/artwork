<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiWeekTotalData extends Data
{
    public function __construct(
        public string $week,
        public int $minutes_before,
        public int $minutes_after,
    ) {
    }
}
