<?php

namespace Tests\Unit\Modules\Room\Services;

use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\Room\Services\RoomCategoryService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomCategoryServiceTest extends TestCase
{
    private RoomCategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RoomCategoryService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function get_all_returns_created_categories(): void
    {
        RoomCategory::create(['name' => 'Studio']);
        RoomCategory::create(['name' => 'Stage']);

        $result = $this->service->getAll();

        $this->assertGreaterThanOrEqual(2, $result->count());
        $this->assertContainsOnlyInstancesOf(RoomCategory::class, $result);
    }
}
