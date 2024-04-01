<?php

namespace Tests\Unit\App\Casts;

use App\Casts\GermanTimeCast;
use Carbon\Carbon;
use Tests\TestCase;

class GermanTimeCastTest extends TestCase
{
    public function testGet(): void
    {
        $germanTimeCast = new GermanTimeCast();

        $date = Carbon::now();
        $formattedDate = $date->translatedFormat('D, d.m.Y');

        $result = $germanTimeCast->get(null, 'test', $date->toDateTimeString(), []);

        $this->assertEquals($formattedDate, $result);
    }

    public function testSet(): void
    {
        $germanTimeCast = new GermanTimeCast();

        $date = Carbon::now();

        $result = $germanTimeCast->set(null, 'test', $date->toDateTimeString(), []);

        $this->assertEquals($date->toDateTimeString(), $result);
    }
}
