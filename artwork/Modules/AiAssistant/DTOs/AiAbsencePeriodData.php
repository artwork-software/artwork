<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Ein Abwesenheitszeitraum (S4). Aufeinanderfolgende ganztaegige Tage gleicher Art sind zusammengefasst.
 * `from`/`to` als `YYYY-MM-DD`; `start_time`/`end_time` (`HH:MM`) nur bei stundenweiser Abwesenheit.
 * `type`: `vacation` (OFF_WORK) oder `not_available` (NOT_AVAILABLE).
 */
class AiAbsencePeriodData extends Data
{
    public function __construct(
        public string $from,
        public string $to,
        public bool $full_day,
        public ?string $start_time,
        public ?string $end_time,
        public string $type,
    ) {
    }
}
