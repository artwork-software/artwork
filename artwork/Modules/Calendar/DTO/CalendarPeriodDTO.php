<?php

namespace Artwork\Modules\Calendar\DTO;

use Illuminate\Support\Collection;
use Artwork\Core\Dto\TransformsWithoutDataPipeline;
use Spatie\LaravelData\Data;

class CalendarPeriodDTO extends Data
{
    use TransformsWithoutDataPipeline;

    public function __construct(
        public string $date,
        public ?Collection $holidays,
        public ?array $hoursOfDay,
        public bool $isExtraRow,
        public ?array $dayRemark = null,
    ) {
    }
}
