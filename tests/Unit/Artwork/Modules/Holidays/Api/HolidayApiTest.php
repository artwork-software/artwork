<?php

namespace Tests\Unit\Artwork\Modules\Holidays\Api;

use Artwork\Modules\Holidays\Api\HolidayApi;
use Artwork\Modules\Holidays\Api\MockHolidaysApi;
use Carbon\Carbon;
use Tests\TestCase;

class HolidayApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
       # $this->app->bind(HolidayApi::class, MockHolidaysApi::class);
    }

    public function testGetHolidays(): void
    {
        $api = $this->app->get(HolidayApi::class);

        $holidays = $api->getHolidays(Carbon::now()->startOf('year'), Carbon::now()->endOf('year'), 'DE-BW');

        $a = 1;
    }
}
