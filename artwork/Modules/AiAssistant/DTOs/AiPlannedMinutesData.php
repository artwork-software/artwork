<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Bereits geplante effektive Minuten (abzueglich Pausen) je ISO-Woche und Monat.
 */
class AiPlannedMinutesData extends Data
{
    /**
     * @param AiWeekMinutesData[] $weeks
     * @param AiMonthMinutesData[] $months
     */
    public function __construct(
        public array $weeks,
        public array $months,
    ) {
    }
}
