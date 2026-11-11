<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Termin der Tagesuebersicht (ai-scenarios-3 S1). Zeiten ISO 8601, Haus-Zeitzone.
 */
class AiDayEventData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $start,
        public ?string $end,
        public bool $all_day,
        public ?AiRefData $room,
        public ?AiRefData $project,
        public ?AiRefData $event_type,
    ) {
    }
}
