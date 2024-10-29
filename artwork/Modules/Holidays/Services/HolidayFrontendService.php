<?php

namespace Artwork\Modules\Holidays\Services;

use Artwork\Modules\Holidays\Frontend\ShowDto;
use Artwork\Modules\Holidays\Models\Holiday;

class HolidayFrontendService
{
    public function __construct()
    {
    }

    public function createShowDto(Holiday $holiday): ShowDto
    {
        return new ShowDto($holiday);
    }
}
