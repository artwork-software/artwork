<?php

namespace Tests\Unit\Artwork\Modules\Availability\Models;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class AvailabilitiesConflictTest extends TestCase
{
    protected MockObject|AvailabilitiesConflict $availabilitiesConflict;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availabilitiesConflict = $this->getMockBuilder(AvailabilitiesConflict::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();
    }

    public function testGetDateCastedAttribute(): void
    {
        $date = '2023-03-01';
        $expected = '01.03.2023';

        $this->availabilitiesConflict->method('getAttribute')
            ->with('date')
            ->willReturn($date);

        $result = $this->availabilitiesConflict->getDateCastedAttribute();

        $this->assertEquals($expected, $result);
    }
}
