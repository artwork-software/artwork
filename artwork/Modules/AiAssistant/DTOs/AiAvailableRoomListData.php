<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiAvailableRoomListData extends Data
{
    /** @param AiAvailableRoomData[] $rooms */
    public function __construct(
        public AiPeriodData $window,
        public array $rooms,
        public int $total_free,
        public int $total_rooms,
        public bool $truncated,
    ) {
    }
}
