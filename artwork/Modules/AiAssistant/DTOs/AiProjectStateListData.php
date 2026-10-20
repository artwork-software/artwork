<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Projektstatus zum Nachschlagen (ai-scenarios-4 S18).
 */
class AiProjectStateListData extends Data
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
