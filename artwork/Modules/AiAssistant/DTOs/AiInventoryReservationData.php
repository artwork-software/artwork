<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Die naechste Reservierung eines Artikels im abgefragten Zeitraum (core-api-inventory).
 *
 * `project` bleibt leer, wenn die Ausgabe zu keinem Projekt gehoert (externer Leihschein) **oder**
 * das Projekt fuer die fragende Person nicht sichtbar ist: die belegte Menge ist ein Faktum, der
 * Projektname nicht (gleiche Trennung wie bei der Raumbelegung, B1 §5).
 */
class AiInventoryReservationData extends Data
{
    public function __construct(
        public ?AiRefData $project,
        public AiPeriodData $period,
        public int $quantity,
    ) {
    }
}
