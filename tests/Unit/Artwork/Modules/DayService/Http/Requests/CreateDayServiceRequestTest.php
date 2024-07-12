<?php

namespace Tests\Unit\Artwork\Modules\DayService\Http\Requests;

use Artwork\Modules\DayService\Http\Requests\CreateDayServiceRequest;
use PHPUnit\Framework\TestCase;

class CreateDayServiceRequestTest extends TestCase
{
    public function testRules(): void
    {
        $this->assertSame(
            [
                'name' => 'required',
                'icon' => 'required',
                'hex_color' => 'required',
            ],
            (new CreateDayServiceRequest())->rules()
        );
    }
}
