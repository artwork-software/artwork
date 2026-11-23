<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Events\RoomRequestsChanged;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Support\Facades\Event as EventFacade;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Eingehende Anfragen: Zahl offener Anfragen, Sammelantwort auf Raumbelegungsanfragen und der
 * gemeinsame Hinweis-Kanal, über den die Seite live bleibt.
 */
final class EventVerificationInboxLiveRoomsTest extends FeatureTestCase
{
    private function roomRequest(Room $room, array $attributes = []): Event
    {
        return Event::factory()->create($attributes + [
            'room_id' => $room->id,
            'occupancy_option' => true,
            'is_planning' => false,
        ]);
    }

    private function closeAllOpenRoomRequests(): void
    {
        Event::query()->where('occupancy_option', true)->update(['occupancy_option' => false]);
    }

    #[Test]
    public function the_inbox_page_provides_the_project_tab_for_links(): void
    {
        $this->actingAsAdmin();

        $this->get(route('event-verifications.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('EventVerification/Index')
                ->where('roomRequestScope', 'all')
                ->has('first_project_tab_id'));
    }

    #[Test]
    public function a_room_admin_counts_only_requests_of_administered_rooms(): void
    {
        $this->closeAllOpenRoomRequests();
        $user = $this->actingAsUserWith([]);
        $administered = Room::factory()->create();
        $other = Room::factory()->create();
        $administered->users()->attach($user->id, ['is_admin' => true, 'can_request' => false]);
        $this->roomRequest($administered);
        $this->roomRequest($administered);
        $this->roomRequest($other);
        // geplante Termine sind keine Raumanfragen
        $this->roomRequest($administered, ['is_planning' => true]);

        $this->getJson(route('event-verifications.open-count'))->assertOk()->assertExactJson(['count' => 2]);
    }

    #[Test]
    public function someone_without_any_inbox_counts_nothing(): void
    {
        $this->closeAllOpenRoomRequests();
        $this->actingAsUserWith([]);
        $this->roomRequest(Room::factory()->create());

        $this->getJson(route('event-verifications.open-count'))->assertOk()->assertExactJson(['count' => 0]);
    }

    #[Test]
    public function the_count_matches_the_requests_listed_on_the_page(): void
    {
        $this->closeAllOpenRoomRequests();
        $this->actingAsUserWith(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $room = Room::factory()->create();
        $this->roomRequest($room);
        $this->roomRequest($room);

        $this->getJson(route('event-verifications.open-count'))->assertExactJson(['count' => 2]);
        $this->get(route('event-verifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('roomBookingRequests.' . $room->id, 2));
    }

    #[Test]
    public function several_room_requests_are_accepted_at_once(): void
    {
        $this->actingAsUserWith(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $room = Room::factory()->create();
        $first = $this->roomRequest($room);
        $second = $this->roomRequest($room);

        $this->from(route('event-verifications.index'))
            ->post(route('events.requests.bulk-answer'), ['events' => [$first->id, $second->id], 'action' => 'accept'])
            ->assertRedirect(route('event-verifications.index'))
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        $this->assertFalse((bool) $first->fresh()->occupancy_option);
        $this->assertFalse((bool) $second->fresh()->occupancy_option);
        $this->assertSame($room->id, $first->fresh()->room_id);
    }

    #[Test]
    public function several_room_requests_are_declined_at_once(): void
    {
        $this->actingAsUserWith(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $room = Room::factory()->create();
        $first = $this->roomRequest($room);
        $second = $this->roomRequest($room);

        $this->post(route('events.requests.bulk-answer'), ['events' => [$first->id, $second->id], 'action' => 'decline'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($first->fresh()->room_id);
        $this->assertSame($room->id, $second->fresh()->declined_room_id);
    }

    #[Test]
    public function requests_the_user_may_not_answer_are_skipped_and_reported(): void
    {
        $user = $this->actingAsUserWith([]);
        $administered = Room::factory()->create();
        $foreign = Room::factory()->create();
        $administered->users()->attach($user->id, ['is_admin' => true, 'can_request' => false]);
        $own = $this->roomRequest($administered);
        $notMine = $this->roomRequest($foreign);

        $this->post(route('events.requests.bulk-answer'), ['events' => [$own->id, $notMine->id], 'action' => 'accept'])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('error');

        $this->assertFalse((bool) $own->fresh()->occupancy_option);
        $this->assertTrue((bool) $notMine->fresh()->occupancy_option);
    }

    #[Test]
    public function a_confirmed_booking_is_never_declined_through_the_bulk_answer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $room = Room::factory()->create();
        $confirmed = Event::factory()->create(['room_id' => $room->id, 'occupancy_option' => false]);

        $this->post(route('events.requests.bulk-answer'), ['events' => [$confirmed->id], 'action' => 'decline'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame($room->id, $confirmed->fresh()->room_id);
    }

    #[Test]
    public function the_bulk_answer_validates_its_input(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('events.requests.bulk-answer'), ['events' => [], 'action' => 'accept'])
            ->assertUnprocessable();
        $this->postJson(route('events.requests.bulk-answer'), ['events' => [1], 'action' => 'delete'])
            ->assertUnprocessable();
    }

    #[Test]
    public function a_new_room_request_sends_one_hint_per_request(): void
    {
        EventFacade::fake([RoomRequestsChanged::class]);
        $room = Room::factory()->create();

        $this->roomRequest($room);
        $this->roomRequest($room);
        // der Hinweis geht gesammelt am Ende des Requests raus
        $this->app->terminate();

        EventFacade::assertDispatchedTimes(RoomRequestsChanged::class, 1);
    }

    #[Test]
    public function an_event_that_is_no_room_request_sends_no_hint(): void
    {
        EventFacade::fake([RoomRequestsChanged::class]);

        $event = Event::factory()->create(['room_id' => Room::factory()->create()->id, 'occupancy_option' => false]);
        $event->update(['eventName' => 'Neuer Name']);
        $this->app->terminate();

        EventFacade::assertNotDispatched(RoomRequestsChanged::class);
    }

    #[Test]
    public function answering_a_room_request_sends_a_hint(): void
    {
        $this->actingAsUserWith(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);
        $request = $this->roomRequest(Room::factory()->create());
        $this->app->terminate();
        EventFacade::fake([RoomRequestsChanged::class]);

        $this->put(route('events.accept', ['event' => $request->id]), ['accepted' => true]);
        $this->app->terminate();

        EventFacade::assertDispatched(RoomRequestsChanged::class);
    }
}
