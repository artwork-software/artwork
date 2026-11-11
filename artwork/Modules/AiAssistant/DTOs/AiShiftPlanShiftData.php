<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Schicht im Dienstplan-Ausschnitt (core-api-schedule-shifts; Konzept B1 §3).
 */
class AiShiftPlanShiftData extends Data
{
    /**
     * @param AiShiftPlanQualificationData[] $qualifications
     * @param AiShiftPlanAssigneeData[] $assigned
     */
    public function __construct(
        public int $id,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $craft,
        public int $required,
        public int $staffed,
        public int $open,
        public bool $committed,
        public array $qualifications,
        public array $assigned,
    ) {
    }
}
