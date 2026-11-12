<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiCraftListData extends Data
{
    /** @param AiCraftData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
