<?php

namespace Tests\Feature\Modules\Room;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Kollisionsanzeige im Termin-Dialog: Anzahl überschneidender Termine je Raum, mit einer Abfrage.
 */
final class RoomCollisionCountTest extends FeatureTestCase
{
    #[Test]
    public function counts_overlapping_events_per_room_and_lists_free_rooms_with_zero(): void
    {
        $this->actingAs(User::factory()->create());
        $busyRoom = Room::factory()->create();
        $freeRoom = Room::factory()->create();
        $trashedRoom = Room::factory()->create();
        $trashedRoom->delete();

        $this->event($busyRoom, '2026-11-02 09:00:00', '2026-11-02 11:00:00');
        $this->event($busyRoom, '2026-11-02 10:30:00', '2026-11-02 12:00:00');
        $this->event($busyRoom, '2026-11-01 08:00:00', '2026-11-03 08:00:00');
        $this->event($busyRoom, '2026-11-02 12:00:00', '2026-11-02 13:00:00');
        $this->event($freeRoom, '2026-11-02 18:00:00', '2026-11-02 19:00:00');
        $this->event($busyRoom, '2026-11-02 10:00:00', '2026-11-02 10:30:00')->delete();

        $counts = $this->postJson(route('collisions.room'), ['params' => [
            'start' => '2026-11-02 10:00:00',
            'end' => '2026-11-02 12:00:00',
        ]])->assertOk()->json();

        $this->assertSame(3, $counts[$busyRoom->id]);
        $this->assertSame(0, $counts[$freeRoom->id]);
        $this->assertArrayNotHasKey($trashedRoom->id, $counts);
    }

    #[Test]
    public function the_edited_event_does_not_collide_with_itself(): void
    {
        $this->actingAs(User::factory()->create());
        $room = Room::factory()->create();
        $edited = $this->event($room, '2026-11-02 10:00:00', '2026-11-02 12:00:00');
        $this->event($room, '2026-11-02 11:00:00', '2026-11-02 13:00:00');

        $counts = $this->postJson(route('collisions.room'), ['params' => [
            'start' => '2026-11-02 10:00:00',
            'end' => '2026-11-02 12:00:00',
            'currentEventId' => (string) $edited->id,
        ]])->assertOk()->json();

        $this->assertSame(1, $counts[$room->id]);
    }

    #[Test]
    public function the_count_needs_a_constant_number_of_queries(): void
    {
        $this->actingAs(User::factory()->create());
        Room::factory()->count(15)->create();

        DB::enableQueryLog();
        $this->postJson(route('collisions.room'), ['params' => [
            'start' => '2026-11-02 10:00:00',
            'end' => '2026-11-02 12:00:00',
        ]])->assertOk();
        $eventQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'from `events`'))
            ->count();
        DB::disableQueryLog();

        $this->assertSame(1, $eventQueries);
    }

    #[Test]
    public function invalid_dates_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('collisions.room'), ['params' => ['start' => 'kein Datum', 'end' => 'x']])
            ->assertStatus(422);
    }

    private function event(Room $room, string $start, string $end): Event
    {
        return Event::factory()->create(['room_id' => $room->id, 'start_time' => $start, 'end_time' => $end]);
    }
}
