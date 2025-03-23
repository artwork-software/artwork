<?php

namespace Artwork\Modules\Calendar\DTO;

use Spatie\LaravelData\Data;

class CalendarHolidayDTO extends Data
{
    public function __construct(
        public string $name,
        public ?string $date,
        public ?string $end_date,
        public ?string $color,
        public ?array $subdivisions,
    ) {
    }
}