<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Offene Schichten des Tages im Gewerks-Scope (S1).
 */
class AiDayOpenShiftsData extends Data
{
    /** @param AiCraftMissingData[] $by_craft */
    public function __construct(
        public int $shifts,
        public int $missing,
        public array $by_craft,
    ) {
    }
}
