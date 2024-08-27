<?php

namespace Tests\Unit\Artwork\Modules\Change\Changes\Room;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Changes\Room\AttributeChange;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\RoomAttribute\Models\RoomAttribute;
use Tests\TestCase;

class AttributeChangeTest extends TestCase
{
    public function testAttributeChange(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create();
        $oldAttribute1 = RoomAttribute::factory()->create();
        $oldAttribute2 = RoomAttribute::factory()->create();
        $oldRoom->attributes()->attach([$oldAttribute1->id, $oldAttribute2->id]);

        $newRoom = Room::factory()->create();
        $newAttribute1 = RoomAttribute::factory()->create();
        $newAttribute2 = RoomAttribute::factory()->create();
        $newRoom->attributes()->attach([$newAttribute1->id, $newAttribute2->id]);

        $changeService = new ChangeService(new ChangeRepository());

        $attributeChange = new AttributeChange($changeService);

        $attributeChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }
}
