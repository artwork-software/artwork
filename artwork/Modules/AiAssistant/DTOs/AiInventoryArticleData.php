<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Inventarartikel mit seiner Verfuegbarkeit im Zeitraum (core-api-inventory; Konzept B1 §3).
 *
 * `total` ist die einsatzbereite Menge, `reserved` die Spitzenlast im Zeitraum (zeitlich getrennte
 * Ausgaben addieren sich nicht), `available` der kleinste freie Bestand — gerechnet von
 * `InventoryArticle::getAvailableStock()`, derselben Methode wie in der Oberflaeche.
 */
class AiInventoryArticleData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?AiRefData $category,
        public float $total,
        public float $reserved,
        public float $available,
        public ?AiInventoryReservationData $next_reservation,
    ) {
    }
}
