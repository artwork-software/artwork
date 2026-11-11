<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Kapazitaet einer Person des Gewerks in der Woche — Felder wie `remaining.weeks[]` am Workload.
 */
class AiCraftCapacityEntryData extends Data
{
    /** @param AiWorkloadAbsenceData[] $absences */
    public function __construct(
        public AiPersonData $person,
        public int $minutes_planned,
        public ?int $minutes_max,
        public ?int $minutes_remaining,
        public ?string $limited_by,
        public array $absences,
    ) {
    }
}
