<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiEventShiftsSummaryData extends Data
{
    public function __construct(
        public int $shifts,
        public int $required,
        public int $staffed,
        public int $open,
        public bool $fully_staffed,
    ) {
    }
}
