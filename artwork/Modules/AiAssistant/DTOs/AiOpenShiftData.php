<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Schicht mit offenem Bedarf (ai-shift-planning, Erweiterung 2 A).
 */
class AiOpenShiftData extends Data
{
    /** @param AiOpenDemandData[] $open */
    public function __construct(
        public int $id,
        public ?AiRefData $craft,
        public ?string $start,
        public ?string $end,
        public ?AiRefData $event,
        public ?AiRefData $room,
        public array $open,
    ) {
    }
}
