<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Auslastung eines Raums (ai-scenarios-4 S17): belegte Minuten als Vereinigung der Termine im
 * Tagesfenster (Ueberlappungen zaehlen einmal), verfuegbare Minuten = Tage × Fenster.
 */
class AiRoomUtilizationData extends Data
{
    /** @param AiEventTypeMinutesData[] $top_event_types */
    public function __construct(
        public AiRefData $room,
        public int $booked_minutes,
        public int $available_minutes,
        public float $utilization_percent,
        public int $events,
        public int $days_booked,
        public array $top_event_types,
    ) {
    }
}
