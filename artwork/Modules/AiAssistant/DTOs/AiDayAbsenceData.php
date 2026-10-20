<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine abwesende Person (S1). `type`: `vacation` oder `not_available`.
 */
class AiDayAbsenceData extends Data
{
    public function __construct(
        public AiPersonRefData $person,
        public string $type,
    ) {
    }
}
