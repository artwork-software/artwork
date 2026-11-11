<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von GET /api/v1/ai/shift-plan (core-api-schedule-shifts; Konzept B1 §3).
 */
class AiShiftPlanData extends Data
{
    /** @param AiShiftPlanShiftData[] $data */
    public function __construct(
        public string $week,
        public AiPeriodData $period,
        public ?AiRefData $craft,
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
