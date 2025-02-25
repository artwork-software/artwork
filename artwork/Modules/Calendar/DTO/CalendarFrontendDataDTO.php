<?php

namespace Artwork\Modules\Calendar\DTO;

use Spatie\LaravelData\Data;

class CalendarFrontendDataDTO extends Data
{
    public function __construct(
        public array $rooms
    ) {
    }
}