<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Schicht einer Person (core-ai-aggregates). `start`/`end` sind die effektiven Zeiten der
 * Zuweisung, `minutes` die Arbeitszeit ohne Pause.
 */
class AiPersonShiftData extends Data
{
    public function __construct(
        public int $shift_id,
        public ?string $start,
        public ?string $end,
        public int $minutes,
        public int $break_minutes,
        public ?AiRefData $craft,
        public ?AiRefData $qualification,
        public ?AiRefData $event,
        public ?AiRefData $project,
        public ?AiRefData $room,
        public bool $committed,
    ) {
    }
}
