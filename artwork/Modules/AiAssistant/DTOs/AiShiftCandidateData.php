<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Person, die die Schicht ohne Konflikt und ohne neuen Regelverstoss bekaeme.
 * Minuten der ISO-Woche der Schicht, **vor** der Zuweisung; `minutes_remaining_week` wie
 * `remaining` am Workload (Vertragsregel `weeklyMaxHours`, sonst Sollzeit, sonst `null`).
 */
class AiShiftCandidateData extends Data
{
    public function __construct(
        public AiPersonData $person,
        public int $minutes_planned_week,
        public ?int $minutes_remaining_week,
    ) {
    }
}
