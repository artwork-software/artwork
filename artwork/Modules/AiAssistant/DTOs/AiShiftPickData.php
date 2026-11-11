<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine regelkonforme Schicht im Vorschlag (ai-shift-planning).
 *
 * Genau eines von `shift_id` (vorhandene Schicht) und `new_shift_index` (Index in `new_shifts`
 * der Anfrage) ist gesetzt. `minutes` sind effektive Minuten abzueglich Pause. `start`/`end` sind
 * ISO 8601 in Haus-Zeitzone; Mitternachtsschichten enden am Folgetag.
 */
class AiShiftPickData extends Data
{
    public function __construct(
        public ?int $shift_id,
        public ?int $new_shift_index,
        public int $shift_qualification_id,
        public string $start,
        public string $end,
        public int $minutes,
        public AiRefData $craft,
        public ?AiRefData $event,
        public ?AiRefData $room,
    ) {
    }
}
