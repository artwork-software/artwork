<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/people/user/{id}/work-time?month=YYYY-MM` (ai-scenarios-3 S7).
 *
 * Monat: `target_minutes` (null, wenn an einem Tag kein Arbeitszeitmuster gilt), `actual_minutes`,
 * `difference_minutes = actual - target` (null mit unbekanntem Soll). Konto (heute, gesamt, aus
 * `OvertimeService::ledgerFor`): `balance_minutes` (Zeitkonto), `open_minutes` (offene Ueberstunden),
 * `payable_minutes` (jetzt auszahlbar; null ohne aktive Ueberstundenregel).
 */
class AiWorkTimeData extends Data
{
    public function __construct(
        public AiPersonData $person,
        public string $month,
        public ?int $target_minutes,
        public int $actual_minutes,
        public ?int $difference_minutes,
        public int $balance_minutes,
        public int $open_minutes,
        public ?int $payable_minutes,
    ) {
    }
}
