<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Sollzeit einer ISO-Woche in Minuten; `null`, sobald an einem Tag der Woche kein gueltiges
 * Arbeitszeitmuster gilt (Soll unbekannt, `WorkTimeCalculationService::summarizeRange()`).
 */
class AiWeekTargetData extends Data
{
    public function __construct(
        public string $week,
        public ?int $minutes,
    ) {
    }
}
