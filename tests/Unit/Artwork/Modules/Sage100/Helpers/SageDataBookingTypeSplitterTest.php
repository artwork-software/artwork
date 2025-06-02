<?php

namespace Tests\Unit\Artwork\Modules\Sage100\Helpers;

use PHPUnit\Framework\TestCase;
use Artwork\Modules\Sage100\Helpers\SageDataBookingTypeSplitter;

class SageDataBookingTypeSplitterTest extends TestCase
{
    public function testSplitDataIntoRegularAndCollectiveBookings(): void
    {
        $items = [
            ['ID' => 1, 'KtoSoll' => 111, 'KtoHaben' => 222, 'KstTraeger' => 'A1'],
            ['ID' => 1, 'KtoSoll' => 111, 'KtoHaben' => 222, 'KstTraeger' => 'A1'],
            ['ID' => 2, 'KtoSoll' => 333, 'KtoHaben' => 444, 'KstTraeger' => 'B1'],
        ];

        $splitter = new SageDataBookingTypeSplitter();
        $result = $splitter->splitDataIntoRegularAndCollectiveBookings($items);

        $this->assertCount(2, $result['collectiveBookings']);
        $this->assertCount(1, $result['singleBookings']);
    }
}
