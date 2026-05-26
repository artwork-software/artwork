<?php

namespace Tests\Unit\Modules\Room\Services;

use Artwork\Modules\Room\Services\RoomRoomAttributeMappingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomRoomAttributeMappingServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(RoomRoomAttributeMappingService::class);

        $this->assertInstanceOf(RoomRoomAttributeMappingService::class, $service);
    }
}
