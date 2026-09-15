<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Treffer aus GET /api/v1/ai/projects (Konzept B1 §3).
 */
class AiProjectSummaryData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $state,
        public AiPeriodData $period,
        public int $room_count,
        public int $event_count,
    ) {
    }
}
