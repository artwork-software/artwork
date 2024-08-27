<?php

namespace Tests\Unit\Artwork\Modules\Change\Changes\Room;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Changes\Room\nameChange;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Room\Models\Room;
use Tests\TestCase;

class NameChangeTest extends TestCase
{
    public function testNameIsChanged(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create(['name' => 'Old name']);
        $newRoom = Room::factory()->create(['name' => 'New name']);

        $changeService = new ChangeService(new ChangeRepository());
        $nameChange = new nameChange($changeService);

        $nameChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }

    public function testNameIsNotChanged(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create(['name' => 'Same name']);
        $newRoom = Room::factory()->create(['name' => 'Same name']);

        $changeService = new ChangeService(new ChangeRepository());
        $nameChange = new nameChange($changeService);

        $nameChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseMissing($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }
}
