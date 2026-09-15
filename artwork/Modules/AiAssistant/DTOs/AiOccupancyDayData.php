<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Belegung eines Raums an einem Tag (Konzept B1 §3).
 *
 * Gezaehlt wird innerhalb des Tagesfensters aus `config('ai.day_window')`; `busy_hours` und
 * `free_hours` ergeben zusammen die Fensterlaenge.
 */
class AiOccupancyDayData extends Data
{
    public function __construct(
        public string $date,
        public float $busy_hours,
        public float $free_hours,
        public int $event_count,
    ) {
    }
}
