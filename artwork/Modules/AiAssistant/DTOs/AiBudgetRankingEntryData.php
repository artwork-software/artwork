<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Projekt der Budget-Rangliste: Summen einer Budgetspalte (core-ai-aggregates).
 */
class AiBudgetRankingEntryData extends Data
{
    public function __construct(
        public AiBudgetProjectData $project,
        public AiBudgetColumnData $column,
        public string $earnings,
        public string $costs,
        public string $result,
        public ?AiBudgetActualData $actual,
    ) {
    }
}
