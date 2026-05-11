<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Services\ShiftGroupService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftGroupServiceTest extends TestCase
{
    private ShiftGroupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftGroupService::class);
    }

    #[Test]
    public function get_all_shift_groups_returns_collection(): void
    {
        ShiftGroup::factory()->count(2)->create();

        $result = $this->service->getAllShiftGroups();

        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    #[Test]
    public function get_shift_group_by_id_returns_model(): void
    {
        $group = ShiftGroup::factory()->create();

        $found = $this->service->getShiftGroupById($group->id);

        $this->assertNotNull($found);
        $this->assertSame($group->id, $found->id);
    }

    #[Test]
    public function get_shift_group_by_id_returns_null_when_missing(): void
    {
        $found = $this->service->getShiftGroupById(99999);

        $this->assertNull($found);
    }

    #[Test]
    public function create_shift_group_persists(): void
    {
        $group = $this->service->createShiftGroup([
            'name' => 'Backstage',
            'color' => '#123456',
            'icon' => 'IconHelmet',
        ]);

        $this->assertNotNull($group->id);
        $this->assertDatabaseHas('shift_groups', ['id' => $group->id, 'name' => 'Backstage']);
    }

    #[Test]
    public function update_shift_group_persists_changes(): void
    {
        $group = ShiftGroup::factory()->create(['name' => 'Old']);

        $this->service->updateShiftGroup($group, ['name' => 'Renamed']);

        $this->assertDatabaseHas('shift_groups', ['id' => $group->id, 'name' => 'Renamed']);
    }

    #[Test]
    public function delete_shift_group_removes_record(): void
    {
        $group = ShiftGroup::factory()->create();

        $this->service->deleteShiftGroup($group);

        $this->assertDatabaseMissing('shift_groups', ['id' => $group->id]);
    }
}
