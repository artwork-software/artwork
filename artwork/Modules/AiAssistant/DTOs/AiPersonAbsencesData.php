<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Eine Person mit ihren Abwesenheiten im Zeitraum (S4).
 */
class AiPersonAbsencesData extends Data
{
    /** @param AiAbsencePeriodData[] $absences */
    public function __construct(
        public AiPersonData $person,
        public array $absences,
    ) {
    }
}
