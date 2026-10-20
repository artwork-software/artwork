<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Fehlende Personen je Gewerk (S1).
 */
class AiCraftMissingData extends Data
{
    public function __construct(
        public AiRefData $craft,
        public int $missing,
    ) {
    }
}
