<?php

namespace Tests\Unit\Modules\Room\Services;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomServiceTest extends TestCase
{
    private RoomService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RoomService::class);
    }

    #[Test]
    public function save_persists_room(): void
    {
        $area = Area::factory()->create();
        $room = Room::factory()->make(['area_id' => $area->id]);

        $saved = $this->service->save($room);

        $this->assertNotNull($saved->id);
        $this->assertDatabaseHas('rooms', ['id' => $saved->id, 'name' => $room->name]);
    }

    #[Test]
    public function delete_soft_deletes_room(): void
    {
        $room = Room::factory()->create();

        $result = $this->service->delete($room);

        $this->assertTrue($result);
        $this->assertSoftDeleted('rooms', ['id' => $room->id]);
    }

    #[Test]
    public function update_modifies_room_attributes(): void
    {
        $room = Room::factory()->create(['name' => 'Old Name']);

        $updated = $this->service->update($room, ['name' => 'New Name']);

        $this->assertSame('New Name', $updated->name);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'name' => 'New Name']);
    }

    #[Test]
    public function duplicate_by_room_model_creates_kopie_room(): void
    {
        $room = Room::factory()->create(['name' => 'Original Room']);

        $copy = $this->service->duplicateByRoomModel($room);

        $this->assertNotSame($room->id, $copy->id);
        $this->assertStringStartsWith('(Kopie) ', $copy->name);
    }

    #[Test]
    public function duplicate_by_room_model_without_area_does_not_attach_to_area(): void
    {
        $room = Room::factory()->create(['name' => 'Source']);

        $copy = $this->service->duplicateByRoomModelWithoutArea($room);

        $this->assertNotSame($room->id, $copy->id);
        $this->assertStringStartsWith('(Kopie) ', $copy->name);
        $this->assertDatabaseHas('rooms', ['id' => $copy->id]);
    }

    #[Test]
    public function get_all_without_trashed_excludes_soft_deleted(): void
    {
        $existing = Room::query()->withTrashed()->pluck('id');
        Room::factory()->count(2)->create();
        $deleted = Room::factory()->create();
        $deleted->delete();

        $rooms = $this->service->getAllWithoutTrashed();

        $this->assertInstanceOf(EloquentCollection::class, $rooms);
        $this->assertFalse($rooms->contains('id', $deleted->id));
        // Two created above are returned alongside any pre-existing rooms.
        $this->assertGreaterThanOrEqual(2, $rooms->count() - $existing->count());
    }

    #[Test]
    public function find_by_name_returns_room_or_null(): void
    {
        $room = Room::factory()->create(['name' => 'Findable']);

        $found = $this->service->findByName('Findable');
        $missing = $this->service->findByName('Not Existing Room');

        $this->assertNotNull($found);
        $this->assertSame($room->id, $found->id);
        $this->assertNull($missing);
    }

    #[Test]
    public function fill_period_with_empty_event_data_creates_keys_per_day(): void
    {
        $room = Room::factory()->create();
        $period = CarbonPeriod::create('2024-01-01', '2024-01-03');

        $result = RoomService::fillPeriodWithEmptyEventData($room, $period);

        $this->assertArrayHasKey('01.01.2024', $result);
        $this->assertArrayHasKey('02.01.2024', $result);
        $this->assertArrayHasKey('03.01.2024', $result);
        $this->assertSame($room->id, $result['01.01.2024']['roomId']);
        $this->assertSame($room->name, $result['01.01.2024']['roomName']);
        $this->assertSame([], $result['01.01.2024']['events']);
    }

    #[Test]
    public function convert_events_for_frontend_returns_empty_skeleton_for_no_events(): void
    {
        $room = Room::factory()->create();
        $period = CarbonPeriod::create('2024-02-01', '2024-02-02');

        $result = $this->service->convertEventsForFrontend($room, [], $period);

        $this->assertSame([], $result['01.02.2024']['events']);
        $this->assertSame([], $result['02.02.2024']['events']);
    }

    #[Test]
    public function get_fallback_room_creates_one_when_missing(): void
    {
        // Ensure none exists and required defaults exist
        Room::query()->where('fallback_room', true)->forceDelete();
        \Artwork\Modules\User\Models\User::factory()->create();
        Area::factory()->create();

        $fallback = $this->service->getFallbackRoom();

        $this->assertTrue((bool) $fallback->fallback_room);
        $this->assertSame('FallbackRoom Room', $fallback->name);
    }

    #[Test]
    public function get_fallback_room_returns_existing_one(): void
    {
        $area = Area::factory()->create();
        $existing = Room::factory()->create([
            'fallback_room' => true,
            'area_id' => $area->id,
            'name' => 'Existing Fallback',
        ]);

        $fallback = $this->service->getFallbackRoom();

        $this->assertSame($existing->id, $fallback->id);
    }
}
