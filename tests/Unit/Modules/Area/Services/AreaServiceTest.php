<?php

namespace Tests\Unit\Modules\Area\Services;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AreaServiceTest extends TestCase
{
    private AreaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AreaService::class);
    }

    #[Test]
    public function get_all_returns_empty_collection_when_no_areas_exist(): void
    {
        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function get_all_returns_all_areas(): void
    {
        Area::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(Area::class, $result);
    }

    #[Test]
    public function create_by_request_persists_area_with_given_name(): void
    {
        $request = Request::create('/areas', 'POST', ['name' => 'Main Stage']);

        $area = $this->service->createByRequest($request);

        $this->assertTrue($area->exists);
        $this->assertSame('Main Stage', $area->name);
        $this->assertDatabaseHas('areas', ['name' => 'Main Stage']);
    }

    #[Test]
    public function update_by_request_updates_area_name(): void
    {
        $area = Area::factory()->create(['name' => 'Old name']);
        $request = Request::create('/areas/' . $area->id, 'PATCH', ['name' => 'New name']);

        $updated = $this->service->updateByRequest($area, $request);

        $this->assertSame('New name', $updated->name);
        $this->assertDatabaseHas('areas', ['id' => $area->id, 'name' => 'New name']);
    }

    #[Test]
    public function delete_soft_deletes_area(): void
    {
        $area = Area::factory()->create();

        $deleted = $this->service->delete($area);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('areas', ['id' => $area->id]);
    }

    #[Test]
    public function duplicate_by_area_model_creates_copy_with_rooms(): void
    {
        $area = Area::factory()->create(['name' => 'Stage']);
        Room::factory()->create(['area_id' => $area->id, 'name' => 'Room A']);

        $roomService = app(RoomService::class);
        $this->service->duplicateByAreaModel($area->fresh('rooms'), $roomService);

        $this->assertDatabaseHas('areas', ['name' => '(Kopie) Stage']);
        $this->assertDatabaseHas('rooms', ['name' => '(Kopie) Room A']);
    }
}
