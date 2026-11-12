<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/events/{event}/shifts` (ai-shift-planning, Erweiterung 2).
 *
 * Besetzung wie im Dienstplan: Bedarf = `shifts_qualifications.value` > 0, besetzt = regulaere
 * (nicht ueberbuchte) Zuweisungen; `open = max(0, required - staffed)`. `fully_staffed` ist wahr,
 * wenn keine Schicht offenen Bedarf hat (auch bei null Schichten).
 */
class AiEventShiftsData extends Data
{
    /** @param AiEventShiftData[] $shifts */
    public function __construct(
        public AiEventShiftsEventData $event,
        public array $shifts,
        public AiEventShiftsSummaryData $summary,
    ) {
    }
}
