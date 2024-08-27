<?php

namespace Tests\Unit\Artwork\Modules\Change\Changes\Room;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Changes\Room\CategoryChange;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\RoomCategory\Models\RoomCategory;
use Tests\TestCase;

class CategoryChangeTest extends TestCase
{
    public function testCategoryChange(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create();
        $oldCategory1 = RoomCategory::factory()->create();
        $oldCategory2 = RoomCategory::factory()->create();
        $oldRoom->categories()->attach([$oldCategory1->id, $oldCategory2->id]);

        $newRoom = Room::factory()->create();
        $newCategory1 = RoomCategory::factory()->create();
        $newCategory2 = RoomCategory::factory()->create();
        $newRoom->categories()->attach([$newCategory1->id, $newCategory2->id]);

        $changeService = new ChangeService(new ChangeRepository());

        $categoryChange = new CategoryChange($changeService);

        $categoryChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }
}
