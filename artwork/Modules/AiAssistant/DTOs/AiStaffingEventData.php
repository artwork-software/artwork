<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Termin der Gewerksbesetzung (S3).
 */
class AiStaffingEventData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $start,
        public ?string $end,
    ) {
    }
}
