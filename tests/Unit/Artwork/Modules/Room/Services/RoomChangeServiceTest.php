<?php

namespace Tests\Unit\Artwork\Modules\Room\Services;

use Artwork\Modules\Change\Changes\Room\RoomChangeFactory;
use Artwork\Modules\Change\Interfaces\RoomChange;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomChangeService;
use Tests\TestCase;

class RoomChangeServiceTest extends TestCase
{
    public function testApplyChangesSuccessfullyAppliesAllChanges(): void
    {
        $room = $this->createMock(Room::class);
        $roomReplicate = $this->createMock(Room::class);
        $roomChange = $this->createMock(RoomChange::class);
        $roomChange->expects($this->once())
            ->method('change')
            ->with($room, $roomReplicate);

        $roomChangeFactory = $this->createMock(RoomChangeFactory::class);
        $roomChangeFactory->method('getRoomChangesAll')
            ->willReturn([$roomChange]);

        $service = new RoomChangeService($roomChangeFactory);
        $service->applyChanges($room, $roomReplicate);

        //need to assert something to satisfy PHPUnit
        $this->assertTrue(true);
    }

    public function testApplyChangesWithNoChangesDoesNothing(): void
    {
        $room = $this->createMock(Room::class);
        $roomReplicate = $this->createMock(Room::class);

        $roomChangeFactory = $this->createMock(RoomChangeFactory::class);
        $roomChangeFactory->method('getRoomChangesAll')
            ->willReturn([]);

        $service = new RoomChangeService($roomChangeFactory);
        $service->applyChanges($room, $roomReplicate);

        //need to assert something to satisfy PHPUnit
        $this->assertTrue(true);
    }
}
