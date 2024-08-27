<?php

namespace Tests\Unit\Artwork\Modules\Change\Changes\Room;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Changes\Room\TemporaryChange;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Room\Models\Room;
use Tests\TestCase;

class TemporaryChangeTest extends TestCase
{
    public function testTemporaryTimePeriodChanged(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create([
            'temporary' => true,
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-31'
        ]);
        $newRoom = Room::factory()->create([
            'temporary' => true,
            'start_date' => '2023-02-01',
            'end_date' => '2023-02-28'
        ]);

        $changeService = new ChangeService(new ChangeRepository());
        $temporaryChange = new TemporaryChange($changeService);

        $temporaryChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }

    public function testTemporaryTimeDeleted(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create([
            'temporary' => true,
            'start_date' => '2023-01-01 00:00:00',
            'end_date' => '2023-01-31 00:00:00'
        ]);
        $newRoom = Room::factory()->create([
            'temporary' => false,
            'start_date' => '2023-01-01 00:00:00',
            'end_date' => '2023-01-31 00:00:00'
        ]);

        $changeService = new ChangeService(new ChangeRepository());
        $temporaryChange = new TemporaryChange($changeService);

        $temporaryChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }

    public function testTemporaryTimeAdded(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create(['temporary' => false]);
        $newRoom = Room::factory()->create(['temporary' => true]);

        $changeService = new ChangeService(new ChangeRepository());
        $temporaryChange = new TemporaryChange($changeService);

        $temporaryChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }

    public function testNoTemporaryChange(): void
    {
        $this->actingAs($this->adminUser());

        $oldRoom = Room::factory()->create(
            ['temporary' => true,
                'start_date' => '2023-01-01',
                'end_date' => '2023-01-31'
            ]
        );
        $newRoom = Room::factory()->create([
            'temporary' => true,
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-31'
        ]);

        $changeService = new ChangeService(new ChangeRepository());
        $temporaryChange = new TemporaryChange($changeService);

        $temporaryChange->change($newRoom, $oldRoom);

        $table = (new Change())->getTable();

        $this->assertDatabaseHas($table, [
            'model_type' => Room::class,
            'model_id' => $newRoom->id,
            'change_type' => 'updated',
        ]);
    }
}
