<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Freie Kapazitaet einer ISO-Woche (ai-shift-planning, Erweiterung 1).
 *
 * `minutes_max` kommt aus der aktiven Vertragsregel `weeklyMaxHours` (`limited_by =
 * "weeklyMaxHours"`), sonst aus der Sollzeit der Woche (`"target"`), sonst `null`.
 * `minutes_remaining = max(0, minutes_max - minutes_planned)` bzw. `null`.
 */
class AiRemainingWeekData extends Data
{
    public function __construct(
        public string $week,
        public int $minutes_planned,
        public ?int $minutes_max,
        public ?int $minutes_remaining,
        public ?string $limited_by,
    ) {
    }
}
