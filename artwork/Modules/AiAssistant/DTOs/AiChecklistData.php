<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Checkliste zum Nachschlagen (ai-scenarios-4 S10). `private` = private Liste (Spalte `private`).
 */
class AiChecklistData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?AiRefData $project,
        public bool $private,
    ) {
    }
}
