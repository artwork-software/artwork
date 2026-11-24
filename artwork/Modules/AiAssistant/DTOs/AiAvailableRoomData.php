<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein freier Raum (core-ai-aggregates). `requires_request`: die Person darf den Raum nicht direkt
 * buchen, sondern nur anfragen — dieselbe Entscheidung wie `EventPolicy::book()`.
 */
class AiAvailableRoomData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?AiRefData $area,
        /** @var list<string> Raumkategorien */
        public array $categories,
        public bool $requires_request,
    ) {
    }
}
