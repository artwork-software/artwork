<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/projects/{project}/budget-summary` (ai-scenarios-3 S2).
 *
 * Betraege als Strings mit Punkt und zwei Nachkommastellen. Ohne Budgettabelle: `has_budget = false`,
 * alle Betragsfelder null und Listen leer. `result = earnings - costs`, `profitable = result > 0`.
 */
class AiBudgetSummaryData extends Data
{
    /**
     * @param AiBudgetMainPositionData[] $main_positions
     * @param AiBudgetMoneySourceData[] $money_sources
     */
    public function __construct(
        public AiBudgetProjectData $project,
        public bool $has_budget,
        public ?string $currency,
        public ?AiBudgetColumnData $column,
        public ?string $costs,
        public ?string $earnings,
        public ?string $result,
        public ?bool $profitable,
        public ?AiBudgetCommentedData $commented,
        public array $main_positions,
        public array $money_sources,
        public ?AiBudgetActualData $actual,
    ) {
    }
}
