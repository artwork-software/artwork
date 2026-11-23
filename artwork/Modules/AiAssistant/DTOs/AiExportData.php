<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von POST /api/v1/ai/exports: relative URL fuer die KI-Bar (Session-Login, gleiche Herkunft),
 * Dateiname fuer den Download und Ablauf in ISO 8601.
 */
class AiExportData extends Data
{
    public function __construct(
        public string $id,
        public string $url,
        public string $filename,
        public int $row_count,
        public string $expires_at,
    ) {
    }
}
