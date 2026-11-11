<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Antwort von `GET /api/v1/ai/rooms/conflicts` (S6).
 */
class AiRoomConflictListData extends Data
{
    /** @param AiRoomConflictData[] $data */
    public function __construct(
        public array $data,
        public int $total,
        public int $limit,
        public bool $truncated,
    ) {
    }
}
