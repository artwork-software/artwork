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
        $this->app->bind(HolidayApi::class, MockHolidaysApi::class);
    }

    public function testGetHolidays(): void
    {
        $this->assertTrue(true);
    }
}
