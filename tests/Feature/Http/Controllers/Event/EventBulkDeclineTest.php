<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktion "Raumanfragen gesammelt ablehnen" (Rechte deckt die Rollen-Matrix ab).
 */
final class EventBulkDeclineTest extends FeatureTestCase
{
    private User $roomAdmin;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roomAdmin = User::factory()->create();
        $this->room = Room::factory()->create(['everyone_can_book' => false]);
        $this->room->users()->attach($this->roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $this->actingAs($this->roomAdmin);
    }

    #[Test]
    public function room_requests_are_declined_and_room_is_remembered(): void
    {
        $first = $this->roomRequest();
        $second = $this->roomRequest();

        $this->put(route('events.bulk-decline'), ['eventIds' => [$first->id, $second->id]])->assertRedirect();

        foreach ([$first, $second] as $event) {
            $fresh = $event->fresh();
            $this->assertNull($fresh->room_id);
            $this->assertSame($this->room->id, (int) $fresh->declined_room_id);
            $this->assertFalse((bool) $fresh->accepted);
            $this->assertFalse((bool) $fresh->occupancy_option);
        }
    }

    #[Test]
    public function comment_is_stored_on_every_declined_event(): void
    {
        $first = $this->roomRequest();
        $second = $this->roomRequest();

        $this->put(route('events.bulk-decline'), [
            'eventIds' => [$first->id, $second->id],
            'comment' => 'Bühne wird renoviert',
        ])->assertRedirect();

        foreach ([$first, $second] as $event) {
            $this->assertSame(
                ['Bühne wird renoviert'],
                $event->comments()->pluck('comment')->all()
            );
        }
    }

    #[Test]
    public function mixed_selection_skips_already_answered_and_foreign_events(): void
    {
        $request = $this->roomRequest();
        $alreadyDeclined = $this->roomRequest();
        $alreadyDeclined->update(['room_id' => null, 'declined_room_id' => $this->room->id]);
        $foreignRoomRequest = Event::factory()->create([
            'room_id' => Room::factory()->create(['everyone_can_book' => false])->id,
            'occupancy_option' => true,
            'is_planning' => false,
        ]);

        $this->put(route('events.bulk-decline'), [
            'eventIds' => [$request->id, $alreadyDeclined->id, $foreignRoomRequest->id],
        ])->assertRedirect();

        $this->assertNull($request->fresh()->room_id);
        $this->assertNotNull($foreignRoomRequest->fresh()->room_id);
        $this->assertSame(0, $alreadyDeclined->comments()->count());
    }

    #[Test]
    public function selection_without_answerable_request_is_forbidden(): void
    {
        $confirmedEvent = Event::factory()->create([
            'room_id' => $this->room->id,
            'occupancy_option' => false,
            'is_planning' => false,
        ]);

        $this->putJson(route('events.bulk-decline'), ['eventIds' => [$confirmedEvent->id]])->assertForbidden();

        $this->assertSame($this->room->id, (int) $confirmedEvent->fresh()->room_id);
    }

    #[Test]
    public function invalid_payload_is_rejected(): void
    {
        $request = $this->roomRequest();

        $this->putJson(route('events.bulk-decline'), ['eventIds' => []])
            ->assertStatus(422)->assertJsonValidationErrors('eventIds');
        $this->putJson(route('events.bulk-decline'), ['eventIds' => [99999999]])
            ->assertStatus(422)->assertJsonValidationErrors('eventIds.0');
        $this->putJson(route('events.bulk-decline'), ['eventIds' => [$request->id, $request->id]])
            ->assertStatus(422);
        $this->putJson(route('events.bulk-decline'), [
            'eventIds' => [$request->id],
            'comment' => str_repeat('x', 5001),
        ])->assertStatus(422)->assertJsonValidationErrors('comment');

        $this->assertSame($this->room->id, (int) $request->fresh()->room_id);
    }

    private function roomRequest(): Event
    {
        return Event::factory()->create([
            'room_id' => $this->room->id,
            'occupancy_option' => true,
            'is_planning' => false,
        ]);
    }
}
