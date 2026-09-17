<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Raum als Nachschlage-Treffer (core-api-lookups; Konzept B1 §3).
 *
 * Bewusst flach: das Modell braucht die ID, um sie in `create_events` einzusetzen, und den Bereich
 * nur zur Unterscheidung gleichnamiger Raeume.
 */
class AiRoomData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?AiRefData $area,
    ) {
    }
}
