<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Vertrag (ai-scenarios-4 S15). `deadline` als `Y-m-d`, `overdue` = Deadline vor heute,
 * `amount` als String mit Punkt und 2 Nachkommastellen, `currency` = Name der Waehrung.
 */
class AiContractData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $contract_state,
        public ?string $deadline,
        public bool $overdue,
        public ?string $amount,
        public ?string $currency,
        public ?AiRefData $project,
    ) {
    }
}
