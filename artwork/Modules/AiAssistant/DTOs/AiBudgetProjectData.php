<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Projekt der Budgetzusammenfassung (S2).
 */
class AiBudgetProjectData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public bool $is_group,
    ) {
    }
}
