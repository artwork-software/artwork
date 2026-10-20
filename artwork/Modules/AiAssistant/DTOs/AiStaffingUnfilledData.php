<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein unbesetzt gebliebener Bedarf (S3). `reason_code`: `no_eligible_people`,
 * `all_candidates_violate_rules` oder `limit`.
 */
class AiStaffingUnfilledData extends Data
{
    public function __construct(
        public int $shift_id,
        public AiRefData $qualification,
        public int $missing,
        public string $reason_code,
    ) {
    }
}
