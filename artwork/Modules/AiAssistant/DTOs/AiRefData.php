<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Artwork\Modules\AiAssistant\Support\AiText;
use Spatie\LaravelData\Data;

/**
 * ID plus Beschriftung — die flache Form, in der Raum, Projekt und Termintyp an den Antworten
 * haengen (Konzept B1 §4: keine verschachtelten Baeume).
 */
class AiRefData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
    ) {
    }

    public static function make(?int $id, ?string $name): ?self
    {
        return $id === null ? null : new self($id, AiText::truncate($name));
    }
}
