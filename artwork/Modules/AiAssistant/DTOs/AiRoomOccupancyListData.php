<?php

namespace Artwork\Modules\AiAssistant\DTOs;

use Spatie\LaravelData\Data;

class AiRoomOccupancyListData extends Data
{
    /** @param AiRoomOccupancyData[] $data */
    public function __construct(
        public array $data,
        public AiPeriodData $period,
        public AiDayWindowData $day_window,
    ) {
    }
}
