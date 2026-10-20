<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine aktive Vertragsregel (`shift_rules`) der Person (ai-shift-planning).
 *
 * `value` ist `individual_number_value` in der Einheit des Regeltyps (meist Stunden),
 * `period_weeks` nur bei `averageWeeklyHours` gesetzt.
 */
class AiShiftRuleData extends Data
{
    public function __construct(
        public string $trigger_type,
        public string $label,
        public ?float $value,
        public ?int $period_weeks,
    ) {
    }
}
