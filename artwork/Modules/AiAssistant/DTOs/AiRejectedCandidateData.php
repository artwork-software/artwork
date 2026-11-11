<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Person aus Gewerk und Funktion, die die Schicht nicht bekaeme — `reason_code` wie beim
 * Vorschlag (`overlap`, `vacation`, `unavailable`, `rule:<trigger_type>`), `reason` als Text.
 */
class AiRejectedCandidateData extends Data
{
    public function __construct(
        public AiPersonRefData $person,
        public string $reason_code,
        public string $reason,
    ) {
    }
}
