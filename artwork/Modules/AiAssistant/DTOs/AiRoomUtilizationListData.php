<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/rooms/utilization` (ai-scenarios-4 S17), absteigend nach Auslastung.
 */
class AiRoomUtilizationListData extends Data
{
    /** @param AiRoomUtilizationData[] $data */
    public function __construct(
        public AiPeriodData $period,
        public AiUtilizationWindowData $window,
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
