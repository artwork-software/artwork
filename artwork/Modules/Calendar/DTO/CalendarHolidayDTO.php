<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Core\Dto\TransformsWithoutDataPipeline;
use Spatie\LaravelData\Data;

class CalendarHolidayDTO extends Data
{
    use TransformsWithoutDataPipeline;

    public function __construct(
        public string $name,
        public ?string $date,
        public ?string $end_date,
        public ?string $color,
        public ?array $subdivisions,
        public bool $treatAsSpecialDay = false,
    ) {
    }
}
