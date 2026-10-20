<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiOpenShiftListData extends Data
{
    /** @param AiOpenShiftData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
        public AiOpenShiftsSummaryData $summary,
    ) {
    }
}
