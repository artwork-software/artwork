<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Bedarf und Besetzung einer Funktion in einer Schicht (core-api-schedule-shifts).
 *
 * Rechnung wie in der Oberflaeche (ShiftWeekStatusService::applyStaffing(), shiftStaffing.js):
 * Bedarf = `shifts_qualifications.value`, besetzt = regulaere Zuweisungen; ueberbuchte zaehlen
 * nicht mit.
 */
class AiShiftPlanQualificationData extends Data
{
    public function __construct(
        public ?AiRefData $qualification,
        public int $required,
        public int $staffed,
        public int $open,
    ) {
    }
}
