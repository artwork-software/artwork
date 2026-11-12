<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiEventTypeListData extends Data
{
    /** @param AiEventTypeData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
