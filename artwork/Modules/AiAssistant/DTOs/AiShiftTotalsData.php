<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Minuten je ISO-Woche und Monat vor und nach dem Vorschlag (bereits geplant + gewaehlt).
 */
class AiShiftTotalsData extends Data
{
    /**
     * @param AiWeekTotalData[] $weeks
     * @param AiMonthTotalData[] $months
     */
    public function __construct(
        public array $weeks,
        public array $months,
    ) {
    }
}
