<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Regel eines Verstosses. Manuelle Verstoesse ohne Regel: `trigger_type = null`, `label` = Titel.
 */
class AiRuleRefData extends Data
{
    public function __construct(
        public ?string $trigger_type,
        public ?string $label,
    ) {
    }
}
