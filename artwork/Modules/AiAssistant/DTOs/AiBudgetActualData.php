<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ist-Werte aus Sage (gebuchte Betraege der relevanten Spalte); nur bei aktiver Sage-Schnittstelle.
 */
class AiBudgetActualData extends Data
{
    public function __construct(
        public string $costs,
        public string $earnings,
        public string $result,
    ) {
    }
}
