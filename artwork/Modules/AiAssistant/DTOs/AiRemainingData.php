<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiRemainingData extends Data
{
    /** @param AiRemainingWeekData[] $weeks */
    public function __construct(
        public array $weeks,
    ) {
    }
}
