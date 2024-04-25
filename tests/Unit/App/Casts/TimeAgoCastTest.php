<?php

namespace Tests\Unit\App\Casts;

use Artwork\Core\Casts\TimeAgoCast;
use Carbon\Carbon;
use Tests\TestCase;

class TimeAgoCastTest extends TestCase
{
    public function testGet(): void
    {
        $timeAgoCast = new TimeAgoCast();

        $date = Carbon::now();
        $expectedResult = '1 Sekunde zuvor';

        $result = $timeAgoCast->get(null, 'test', $date->toDateTimeString(), []);

        $this->assertEquals($expectedResult, $result);
    }

    public function testSet(): void
    {
        $timeAgoCast = new TimeAgoCast();

        $date = Carbon::now();

        $result = $timeAgoCast->set(null, 'test', $date->toDateTimeString(), []);

        $this->assertEquals($date->toDateTimeString(), $result);
    }
}
