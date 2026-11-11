<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Zusammenfassung einer Vorschau (core-write-endpoints-dry-run).
 *
 * `blocked` zaehlt die Eintraege, an denen ein Rechtekonflikt haengt — sie wuerden bei der
 * Ausfuehrung nicht geschrieben, die Ausfuehrung bricht ab.
 */
class AiWriteSummaryData extends Data
{
    public function __construct(
        public string $action,
        public int $total,
        public int $with_conflicts,
        public int $blocked,
    ) {
    }
}
