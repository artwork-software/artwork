<?php

namespace Tests\Unit\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventCollisionService;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventCollisionServiceTest extends TestCase
{
    private EventCollisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventCollisionService::class);
    }

    #[Test]
    public function get_collision_returns_builder(): void
    {
        $room = Room::factory()->create();
        $request = Request::create('/', 'POST', [
            'start' => '2026-06-01 10:00:00',
            'end' => '2026-06-01 12:00:00',
            'roomId' => $room->id,
        ]);

        $builder = $this->service->getCollision($request);

        $this->assertInstanceOf(Builder::class, $builder);
    }

    #[Test]
    public function get_collision_count_returns_zero_when_no_overlap(): void
    {
        $room = Room::factory()->create();
        $request = Request::create('/', 'POST', [
            'start' => '2026-06-01 10:00:00',
            'end' => '2026-06-01 12:00:00',
            'roomId' => $room->id,
        ]);

        $count = $this->service->getCollisionCount($request);

        $this->assertSame(0, $count);
    }

    #[Test]
    public function get_collision_count_detects_overlapping_event(): void
    {
        $room = Room::factory()->create();
        Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => '2026-06-01 11:00:00',
            'end_time' => '2026-06-01 13:00:00',
        ]);

        $request = Request::create('/', 'POST', [
            'start' => '2026-06-01 10:00:00',
            'end' => '2026-06-01 12:00:00',
            'roomId' => $room->id,
        ]);

        $count = $this->service->getCollisionCount($request);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    #[Test]
    public function get_collision_excludes_supplied_event(): void
    {
        $room = Room::factory()->create();
        $existing = Event::factory()->create([
            'room_id' => $room->id,
            'start_time' => '2026-06-01 11:00:00',
            'end_time' => '2026-06-01 13:00:00',
        ]);

        $request = Request::create('/', 'POST', [
            'start' => '2026-06-01 10:00:00',
            'end' => '2026-06-01 12:00:00',
            'roomId' => $room->id,
        ]);

        $collisions = $this->service->getCollision($request, $existing)->get();

        $this->assertFalse($collisions->contains('id', $existing->id));
    }

    #[Test]
    public function get_conflict_events_returns_formatted_array(): void
    {
        $room = Room::factory()->create();
        Event::factory()->create([
            'room_id' => $room->id,
            'eventName' => 'Test Event',
            'start_time' => '2026-06-01 11:00:00',
            'end_time' => '2026-06-01 13:00:00',
        ]);

        $request = Request::create('/', 'POST', [
            'start' => '2026-06-01 10:00:00',
            'end' => '2026-06-01 12:00:00',
            'roomId' => $room->id,
        ]);

        $conflicts = $this->service->getConflictEvents($request);

        $this->assertIsArray($conflicts);
        $this->assertNotEmpty($conflicts);
        $this->assertArrayHasKey('id', $conflicts[0]);
        $this->assertArrayHasKey('title', $conflicts[0]);
        $this->assertArrayHasKey('event', $conflicts[0]);
    }
}
