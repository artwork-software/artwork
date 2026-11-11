<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Termin einer Doppelbelegung (S6). Termine aus Projekten, die die fragende Person nicht sehen
 * darf, erscheinen anonym: `id = null`, `name = "belegt"`, `project = null`.
 */
class AiConflictEventData extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $project,
    ) {
    }
}
