<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Zeile der Stundenuebersicht (ai-scenarios-4 S16), Werte wie `AiWorkTimeData` (S7).
 */
class AiWorkTimeOverviewEntryData extends Data
{
    public function __construct(
        public AiPersonData $person,
        public ?int $target_minutes,
        public int $actual_minutes,
        public ?int $difference_minutes,
        public int $balance_minutes,
    ) {
    }
}
