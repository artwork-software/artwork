<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/crafts/{craft}/capacity?week=YYYY-Www` (Erweiterung 2 D).
 * `data` sortiert nach `minutes_remaining` absteigend (unbekannt zuletzt), dann Name.
 */
class AiCraftCapacityData extends Data
{
    /** @param AiCraftCapacityEntryData[] $data */
    public function __construct(
        public AiRefData $craft,
        public string $week,
        public array $data,
    ) {
    }
}
