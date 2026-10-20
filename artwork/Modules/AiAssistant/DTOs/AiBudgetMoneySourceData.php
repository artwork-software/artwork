<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Finanzierungsquelle des Projekts (`MoneySourceCalculationService`): `amount` Volumen,
 * `linked_sum` verknuepfte Positionen (Erloese minus Kosten), `remaining = amount + linked_sum`.
 */
class AiBudgetMoneySourceData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public string $amount,
        public string $linked_sum,
        public string $remaining,
    ) {
    }
}
