<?php

namespace Artwork\Modules\Calendar\DTO;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class CalendarPeriodDTO extends Data
{
    public function __construct(
        public string $day,
        public string $dayString,
        public bool $isWeekend,
        public string $fullDay,
        public string $shortDay,
        public string $withoutFormat,
        public string $fullDayDisplay,
        public int $weekNumber,
        public bool $isMonday,
        public int $monthNumber,
        public bool $isSunday,
        public bool $isFirstDayOfMonth,
        public bool $addWeekSeparator,
        public ?Collection $holidays,
        public ?array $hoursOfDay,
        public bool $isExtraRow,
    ) {
    }
}
