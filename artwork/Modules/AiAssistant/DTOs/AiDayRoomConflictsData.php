<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

/**
 * Doppelbelegungen des Tages (S1): Anzahl und die ersten 10.
 */
class AiDayRoomConflictsData extends Data
{
    /** @param AiRoomConflictData[] $data */
    public function __construct(
        public int $count,
        public array $data,
    ) {
    }
}
