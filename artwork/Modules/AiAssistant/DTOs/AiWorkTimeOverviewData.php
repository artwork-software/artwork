<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/work-time/overview` (ai-scenarios-4 S16), absteigend nach `sort`.
 * `total` = Personen mit Arbeitszeitkonto im Filter, `truncated` wenn mehr als `limit`.
 */
class AiWorkTimeOverviewData extends Data
{
    /** @param AiWorkTimeOverviewEntryData[] $data */
    public function __construct(
        public string $month,
        public ?AiRefData $craft,
        public string $sort,
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
