<?php

namespace Tests\Unit\Artwork\Modules\Availability\Models;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    protected MockObject|Availability $availability;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability = $this->getMockBuilder(Availability::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'conflicts'])
            ->getMock();
    }

    public function testFormattedDateReturnsCorrectFormat(): void
    {
        $date = '2023-03-01';
        $expected = '01.03.2023';

        $this->availability->method('getAttribute')
            ->with('date')
            ->willReturn($date);

        $result = $this->availability->getFormattedDateAttribute();

        $this->assertEquals($expected, $result);
    }

    public function testDateCastedReturnsCorrectFormat(): void
    {
        $date = '2023-03-01';
        $expected = 'Mi, 01.03.2023';

        $this->availability->method('getAttribute')
            ->with('date')
            ->willReturn($date);

        $result = $this->availability->getDateCastedAttribute();

        $this->assertEquals($expected, $result);
    }

    public function testHasConflictsReturnsTrueWhenConflictsExist(): void
    {
        $this->availability->method('conflicts')
            ->willReturn(new AvailabilitiesConflict());

        $result = $this->availability->getHasConflictsAttribute();

        $this->assertTrue($result);
    }

    public function testHasConflictsReturnsFalseWhenNoConflictsExist(): void
    {
        $this->availability->method('conflicts')
            ->willReturn(null);

        $result = $this->availability->getHasConflictsAttribute();

        $this->assertFalse($result);
    }
}
