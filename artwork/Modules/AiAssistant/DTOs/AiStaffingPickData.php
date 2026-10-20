<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Besetzungsvorschlag (S3): Person auf Platz in Funktion. Geht unveraendert in
 * `POST /ai/shift-assignments/bulk` (`person`, `shift_id`, `shift_qualification_id`).
 */
class AiStaffingPickData extends Data
{
    public function __construct(
        public int $shift_id,
        public int $shift_qualification_id,
        public AiRefData $qualification,
        public ?string $start,
        public ?string $end,
        public AiPersonData $person,
    ) {
    }
}
