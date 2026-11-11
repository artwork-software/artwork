<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Die budgetrelevante Spalte (`ColumnRelevanceService::resolveRelevantColumn`).
 */
class AiBudgetColumnData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $sub_name,
    ) {
    }
}
