<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Funktionen (Schicht-Qualifikationen) zum Nachschlagen (ai-scenarios-4 S13).
 */
class AiQualificationListData extends Data
{
    /** @param AiRefData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
