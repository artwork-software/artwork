<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Terminart als Nachschlage-Treffer (core-api-lookups; Konzept B1 §3).
 *
 * `is_default` kommt aus `EventType::$fallback_type` — der Kennzeichnung, die auch die Oberflaeche
 * benutzt. Genau eine Zeile je Antwort traegt sie (siehe `AiLookupReadService`).
 */
class AiEventTypeData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $abbreviation,
        public bool $is_default,
    ) {
    }
}
