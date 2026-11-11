<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiEventShiftData extends Data
{
    /**
     * @param AiEventShiftQualificationData[] $qualifications
     * @param AiEventShiftAssigneeData[] $assignees
     */
    public function __construct(
        public int $id,
        public ?AiRefData $craft,
        public ?string $start,
        public ?string $end,
        public bool $committed,
        public array $qualifications,
        public array $assignees,
    ) {
    }
}
