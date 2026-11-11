<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Auskommentierte Betraege der Spalte (nicht eingerechnet).
 */
class AiBudgetCommentedData extends Data
{
    public function __construct(
        public string $costs,
        public string $earnings,
    ) {
    }
}
