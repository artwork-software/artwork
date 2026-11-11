<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Aufgabe (ai-scenarios-3 S1/S5). `deadline` ISO 8601 oder null; `overdue` = offen und Deadline
 * vor jetzt; `description` auf 300 Zeichen gekuerzt.
 */
class AiTaskData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $description,
        public ?string $deadline,
        public bool $overdue,
        public bool $done,
        public AiRefData $checklist,
        public ?AiRefData $project,
    ) {
    }
}
