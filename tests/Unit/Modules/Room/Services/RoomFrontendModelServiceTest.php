<?php

namespace Tests\Unit\Modules\Room\Services;

use Artwork\Modules\Room\DTOs\ShowDto;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomFrontendModelService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomFrontendModelServiceTest extends TestCase
{
    private RoomFrontendModelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RoomFrontendModelService::class);
    }

    #[Test]
    public function create_show_dto_returns_dto_instance(): void
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $dto = $this->service->createShowDto($room, $user);

        $this->assertInstanceOf(ShowDto::class, $dto);
    }
}
