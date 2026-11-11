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
    ) {
    }
}
