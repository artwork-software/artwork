<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/shifts/{shift}/candidates` (Erweiterung 2 C). Geprueft werden
 * hoechstens `ai.list_limit` Personen; `truncated` sagt, ob es mehr in Frage kommende gab.
 */
class AiShiftCandidateListData extends Data
{
    /**
     * @param AiShiftCandidateData[] $data
     * @param AiRejectedCandidateData[] $rejected
     */
    public function __construct(
        public AiCandidateShiftData $shift,
        public array $data,
        public array $rejected,
        public bool $truncated,
    ) {
    }
}
