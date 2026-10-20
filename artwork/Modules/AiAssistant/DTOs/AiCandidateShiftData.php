<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Die Schicht, fuer die Kandidat:innen gesucht werden (Erweiterung 2 C). `open` ist der offene
 * regulaere Bedarf in `qualification`.
 */
class AiCandidateShiftData extends Data
{
    public function __construct(
        public int $id,
        public ?AiRefData $craft,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $event,
        public ?AiRefData $room,
        public AiRefData $qualification,
        public int $open,
    ) {
    }
}
