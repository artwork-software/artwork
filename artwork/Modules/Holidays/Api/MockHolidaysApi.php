<?php

namespace Artwork\Modules\Holidays\Api;

use Artwork\Modules\Holidays\Models\Subdivision;
use Carbon\Carbon;

class MockHolidaysApi implements HolidayApi
{
    public function getHolidays(
        Carbon $from,
        Carbon $to,
        Subdivision $subdivision,
        ?string $languageCode = 'DE',
    ): array {
        //@todo tests
        return [];
    }

}
