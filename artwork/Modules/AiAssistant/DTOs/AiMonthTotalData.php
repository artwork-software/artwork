<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiMonthTotalData extends Data
{
    public function __construct(
        public string $month,
        public int $minutes_before,
        public int $minutes_after,
    ) {
    }
}
