<?php

namespace Tests\Unit\Modules\Room\Services;

use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomChangeService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomChangeServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(RoomChangeService::class);

        $this->assertInstanceOf(RoomChangeService::class, $service);
    }

    #[Test]
    public function apply_changes_runs_without_throwing(): void
    {
        $room = Room::factory()->create(['name' => 'A']);
        $replicate = Room::factory()->make(['name' => 'B', 'area_id' => $room->area_id]);

        $service = app(RoomChangeService::class);

        $service->applyChanges($room, $replicate);

        $this->assertTrue(true);
    }
}
