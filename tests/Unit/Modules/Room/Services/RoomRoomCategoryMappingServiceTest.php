<?php

namespace Tests\Unit\Modules\Room\Services;

use Artwork\Modules\Room\Services\RoomRoomCategoryMappingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomRoomCategoryMappingServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(RoomRoomCategoryMappingService::class);

        $this->assertInstanceOf(RoomRoomCategoryMappingService::class, $service);
    }
}
