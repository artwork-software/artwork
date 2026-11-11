<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiInventoryListData extends Data
{
    /** @param AiInventoryArticleData[] $data */
    public function __construct(
        public array $data,
        public AiPeriodData $period,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
