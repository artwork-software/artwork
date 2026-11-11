<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Abwesende des Tages (S1): Anzahl Personen und die ersten 10.
 */
class AiDayAbsencesData extends Data
{
    /** @param AiDayAbsenceData[] $data */
    public function __construct(
        public int $count,
        public array $data,
    ) {
    }
}
