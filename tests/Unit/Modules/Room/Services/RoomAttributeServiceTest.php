<?php

namespace Tests\Unit\Modules\Room\Services;

use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Services\RoomAttributeService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomAttributeServiceTest extends TestCase
{
    private RoomAttributeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RoomAttributeService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function get_all_returns_created_attributes(): void
    {
        RoomAttribute::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertGreaterThanOrEqual(3, $result->count());
        $this->assertContainsOnlyInstancesOf(RoomAttribute::class, $result);
    }
}
