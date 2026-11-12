<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiRuleViolationListData extends Data
{
    /** @param AiRuleViolationData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
