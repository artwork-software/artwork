<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Offener regulaerer Bedarf in einer Funktion (`missing` = Bedarf minus regulaere Besetzung, > 0).
 */
class AiOpenDemandData extends Data
{
    public function __construct(
        public AiRefData $qualification,
        public int $missing,
    ) {
    }
}
