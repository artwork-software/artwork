<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine eingeteilte Person im Dienstplan-Ausschnitt (core-api-schedule-shifts).
 *
 * Bewusst **nur Anzeigename und Funktion**: der Dienstplan zeigt in der Oberflaeche ebenfalls
 * nur den Namen. Keine ID, keine Kontaktdaten — die gehoeren nicht in den Modellkontext.
 */
class AiShiftPlanAssigneeData extends Data
{
    public function __construct(
        public string $display_name,
        public ?string $qualification,
        public bool $overbooked,
    ) {
    }
}
