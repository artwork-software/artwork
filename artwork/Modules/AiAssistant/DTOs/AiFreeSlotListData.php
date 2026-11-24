<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiFreeSlotListData extends Data
{
    /** @param AiFreeSlotData[] $data */
    public function __construct(
        public array $data,
        public AiPeriodData $period,
        public AiDayWindowData $day_window,
        public float $min_hours,
        // core-ai-aggregates: alle gefundenen Slots und ob die Liste gekuerzt ist
        public int $total = 0,
        public bool $truncated = false,
    ) {
    }
}
