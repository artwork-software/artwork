<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine verworfene Schicht mit Grund (ai-shift-planning).
 *
 * `reason_code`: `overlap`, `vacation`, `unavailable`, `limit_week`, `limit_month`, `limit_total`,
 * `rule:<trigger_type>` (z. B. `rule:maxWorkingHoursOnDay`), `excluded`, `not_qualified`.
 */
class AiShiftSkipData extends Data
{
    public function __construct(
        public ?int $shift_id,
        public ?int $new_shift_index,
        public ?string $start,
        public string $reason_code,
        public string $reason,
    ) {
    }
}
