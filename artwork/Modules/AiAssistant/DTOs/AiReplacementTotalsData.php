<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Summen (S9): Schichten der Person, davon mit Ersatz, ohne Ersatz.
 */
class AiReplacementTotalsData extends Data
{
    public function __construct(
        public int $shifts,
        public int $covered,
        public int $uncovered,
    ) {
    }
}
