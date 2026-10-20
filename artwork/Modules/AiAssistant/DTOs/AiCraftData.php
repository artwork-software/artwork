<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Gewerk (Erweiterung 2 D). `plannable`: darf die fragende Person darin Schichten planen.
 */
class AiCraftData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $abbreviation,
        public bool $plannable,
    ) {
    }
}
