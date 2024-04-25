<?php

namespace Tests\Unit\App\Casts;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Carbon\Carbon;
use Tests\TestCase;

class TimeWithoutSecondsTest extends TestCase
{
    public function testGet(): void
    {
        $timeWithoutSeconds = new TimeWithoutSeconds();

        $date = Carbon::now();
        $expectedResult = $date->format('H:i');

        $result = $timeWithoutSeconds->get(null, 'test', $date->toDateTimeString(), []);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetNull(): void
    {
        $timeWithoutSeconds = new TimeWithoutSeconds();

        $result = $timeWithoutSeconds->get(null, 'test', null, []);

        $this->assertNull($result);
    }

    public function testSet(): void
    {
        $timeWithoutSeconds = new TimeWithoutSeconds();

        $date = Carbon::now();

        $result = $timeWithoutSeconds->set(null, 'test', $date->toDateTimeString(), []);

        $this->assertEquals($date->toDateTimeString(), $result);
    }
}
