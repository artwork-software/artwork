<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Summen (S3): offene Plaetze vorher, davon besetzt, unbesetzt.
 */
class AiStaffingTotalsData extends Data
{
    public function __construct(
        public int $slots,
        public int $filled,
        public int $unfilled,
    ) {
    }
}
