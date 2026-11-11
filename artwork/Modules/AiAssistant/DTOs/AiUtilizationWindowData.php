<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Bezugsfenster je Tag (`HH:MM`); `source` = `default` (08:00–24:00) — Raeume haben im Core keine
 * Oeffnungszeiten.
 */
class AiUtilizationWindowData extends Data
{
    public function __construct(
        public string $start,
        public string $end,
        public string $source,
    ) {
    }
}
