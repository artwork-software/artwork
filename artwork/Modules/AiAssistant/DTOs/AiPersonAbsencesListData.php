<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/absences` (S4); `total` zaehlt Personen.
 */
class AiPersonAbsencesListData extends Data
{
    /** @param AiPersonAbsencesData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
