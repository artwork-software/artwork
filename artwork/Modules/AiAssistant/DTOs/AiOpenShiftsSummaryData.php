<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Summe ueber **alle** Schichten mit offenem Bedarf im Zeitraum (nicht nur die ausgelieferte Seite).
 */
class AiOpenShiftsSummaryData extends Data
{
    public function __construct(
        public int $shifts,
        public int $missing,
    ) {
    }
}
