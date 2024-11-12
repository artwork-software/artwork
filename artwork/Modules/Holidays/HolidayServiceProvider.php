<?php

namespace Artwork\Modules\Holidays;

use Artwork\Modules\Holidays\Api\HolidayApi;
use Artwork\Modules\Holidays\Api\OpenHolidaysApi;
use Illuminate\Support\ServiceProvider;

class HolidayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HolidayApi::class, OpenHolidaysApi::class);
    }
}
