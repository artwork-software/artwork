<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * GET /api/v1/ai/projects/budget-ranking (core-ai-aggregates, Entgrenzen P1).
 */
class AiBudgetRankingData extends Data
{
    /**
     * @param AiBudgetRankingEntryData[] $projects
     * @param AiRefData[] $without_budget
     * @param AiBudgetRankingMissingColumnData[] $without_period_column
     */
    public function __construct(
        public ?string $period,
        public string $sort,
        public string $direction,
        public array $projects,
        public array $without_budget,
        public array $without_period_column,
        public int $without_access,
        public int $total,
        public bool $truncated,
    ) {
    }
}
