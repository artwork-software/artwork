<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Der heute gueltige Arbeitsvertrag einer Person (nur der Name, ai-shift-planning).
 */
class AiContractRefData extends Data
{
    public function __construct(
        public string $name,
    ) {
    }
}
