<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Regelverstoss aus `shift_rule_violations` (ai-shift-planning, Erweiterung 2 B), nur lesend.
 * `date` ist `YYYY-MM-DD`, `detail` der Text aus `ShiftRuleViolation::getViolationMessage()`.
 */
class AiRuleViolationData extends Data
{
    public function __construct(
        public int $id,
        public AiPersonRefData $person,
        public AiRuleRefData $rule,
        public ?string $date,
        public ?string $severity,
        public ?string $status,
        public ?string $detail,
        public ?AiRefData $shift,
    ) {
    }
}
