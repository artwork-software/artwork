<?php

namespace Artwork\Modules\Calendar\DTO;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class CalendarPeriodDTO extends Data
{
    public function __construct(
        public string $date,
        public ?Collection $holidays,
        public ?array $hoursOfDay,
        public bool $isExtraRow,
        public ?array $dayRemark = null,
    ) {
    }
}
