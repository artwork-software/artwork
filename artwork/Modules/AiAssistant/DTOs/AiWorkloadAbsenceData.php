<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Abwesenheit einer Person (ai-shift-planning). Aufeinanderfolgende ganztaegige Tage gleicher Art
 * sind zusammengefasst. `from`/`to` sind ISO 8601 in Haus-Zeitzone (ganztaegig: 00:00 bis 23:59),
 * `type` ist `OFF_WORK`, `FREE_WORK` oder `NOT_AVAILABLE`.
 */
class AiWorkloadAbsenceData extends Data
{
    public function __construct(
        public string $from,
        public string $to,
        public string $type,
    ) {
    }
}
