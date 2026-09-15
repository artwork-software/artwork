<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiAvailabilityListData extends Data
{
    /** @param AiAvailabilityData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
