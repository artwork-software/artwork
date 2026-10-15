<?php

namespace Artwork\Modules\Calendar\DTO;

use Artwork\Core\Dto\TransformsWithoutDataPipeline;
use Spatie\LaravelData\Data;

class CalendarFrontendDataDTO extends Data
{
    use TransformsWithoutDataPipeline;

    public function __construct(
        public array $rooms
    ) {
    }
}
