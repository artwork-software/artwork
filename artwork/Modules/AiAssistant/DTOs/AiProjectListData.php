<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Gemeinsame Huelle aller Listen-Antworten (Konzept B1 §4): harte Obergrenze, `truncated` und
 * `total`, damit das Modell weiss, dass es einen Ausschnitt sieht.
 */
class AiProjectListData extends Data
{
    /** @param AiProjectSummaryData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
