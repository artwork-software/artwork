<?php

namespace Tests\Unit\Artwork\Modules\Change\Changes\Room;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Changes\Room\DescriptionChange;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Room\Models\Room;
use Tests\TestCase;

class DescriptionChangeTest extends TestCase
{
    public function testDescriptionIsChanged(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create(['description' => 'Old description']);
        $newRoom = Room::factory()->create(['description' => 'New description']);

        $changeService = new ChangeService(new ChangeRepository());
        $descriptionChange = new DescriptionChange($changeService);

        $descriptionChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }

    public function testDescriptionIsNotChanged(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create(['description' => 'Same description']);
        $newRoom = Room::factory()->create(['description' => 'Same description']);

        $changeService = new ChangeService(new ChangeRepository());
        $descriptionChange = new DescriptionChange($changeService);

        $descriptionChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseMissing($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }
}
