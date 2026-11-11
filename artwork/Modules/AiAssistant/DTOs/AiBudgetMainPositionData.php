<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Summe einer Hauptposition in der relevanten Spalte. `type`: `cost` oder `earning`.
 */
class AiBudgetMainPositionData extends Data
{
    public function __construct(
        public ?string $name,
        public string $type,
        public string $sum,
    ) {
    }
}
