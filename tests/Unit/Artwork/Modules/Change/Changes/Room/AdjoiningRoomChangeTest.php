<?php

namespace Tests\Unit\Artwork\Modules\Change\Changes\Room;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Changes\Room\AdjoiningRoomChange;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Room\Models\Room;
use Tests\TestCase;

class AdjoiningRoomChangeTest extends TestCase
{
    public function testAdjoiningRoomChange(): void
    {
        $this->actingAs($this->adminUser());
        $oldRoom = Room::factory()->create();
        $oldAdjoiningRoom1 = Room::factory()->create();
        $oldAdjoiningRoom2 = Room::factory()->create();
        $oldRoom->adjoining_rooms()->attach([$oldAdjoiningRoom1->id, $oldAdjoiningRoom2->id]);

        $newRoom = Room::factory()->create();
        $newAdjoiningRoom1 = Room::factory()->create();
        $newAdjoiningRoom2 = Room::factory()->create();
        $newRoom->adjoining_rooms()->attach([$newAdjoiningRoom1->id, $newAdjoiningRoom2->id]);

        $changeService = new ChangeService(new ChangeRepository());

        $adjoiningRoomChange = new AdjoiningRoomChange($changeService);

        $adjoiningRoomChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }
}
