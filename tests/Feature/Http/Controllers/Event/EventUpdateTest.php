<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventUpdateTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update_event(): void
    {
        $event = Event::factory()->create();

        $this->putJson(route('events.update', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_update_event_description(): void
    {
        $event = Event::factory()->create();

        $this->patchJson(route('event.update.description', $event), ['description' => 'foo'])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_update_event_description(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->patchJson(route('event.update.description', $event), ['description' => 'New description']);

        $response->assertSuccessful();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'description' => 'New description',
        ]);
    }

    #[Test]
    public function update_description_returns_404_for_unknown_event(): void
    {
        $this->actingAsAdmin();

        $response = $this->patchJson(route('event.update.description', ['event' => PHP_INT_MAX]), ['description' => 'x']);

        $response->assertNotFound();
    }

    #[Test]
    public function admin_update_event_with_invalid_payload_returns_validation_error(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->putJson(route('events.update', $event), []);

        $response->assertStatus(422);
    }

    #[Test]
    public function room_change_without_notifications_does_not_fail(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $room = Room::factory()->create();
        $eventType = EventType::factory()->create();
        $event = Event::factory()->create(['project_id' => $project->id, 'room_id' => $room->id]);

        // Vorher: $projectManagers wurde nur im Benachrichtigungs-Block gesetzt → 500 (Undefined variable)
        $this->putJson(route('events.update', $event), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Raumwechsel',
            'eventName' => 'Probe',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
            'noNotifications' => true,
            'roomChange' => true,
        ])->assertSuccessful();
    }

    #[Test]
    public function guest_cannot_convert_event_to_planning(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('events.convertToPlanning', $event))
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_convert_event_to_planning(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => false]);

        $response = $this->post(route('events.convertToPlanning', $event));

        $response->assertRedirect();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'is_planning' => true,
        ]);
    }

    #[Test]
    public function admin_can_restore_trashed_event(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();
        $event->delete();

        $response = $this->patch(route('events.restore', $event->id));

        $response->assertRedirect(route('events.trashed'));
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function restore_event_returns_404_for_unknown_event(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('events.restore', PHP_INT_MAX));

        $response->assertNotFound();
    }

    #[Test]
    public function guest_cannot_accept_event(): void
    {
        $event = Event::factory()->create();

        $this->putJson(route('events.accept', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_decline_event(): void
    {
        $event = Event::factory()->create();

        $this->putJson(route('events.decline', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function requester_cannot_accept_own_room_request(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $event = Event::factory()->create([
            'user_id' => $requester->id,
            'occupancy_option' => true,
        ]);

        $this->putJson(route('events.accept', $event))->assertForbidden();

        $this->assertTrue($event->fresh()->occupancy_option);
    }

    #[Test]
    public function requester_is_not_authorized_to_confirm_own_room_request_while_editing(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $event = Event::factory()->create([
            'user_id' => $requester->id,
            'occupancy_option' => true,
        ]);

        $this->assertTrue(Gate::forUser($requester)->denies('answerRoomRequest', $event));

        $this->assertTrue($event->fresh()->occupancy_option);
    }

    #[Test]
    public function room_admin_can_accept_a_pending_room_request(): void
    {
        $roomAdmin = User::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'occupancy_option' => true,
        ]);
        $this->actingAs($roomAdmin);

        $this->put(route('events.accept', $event))->assertRedirect();

        $this->assertFalse($event->fresh()->occupancy_option);
    }

    #[Test]
    public function room_owner_can_answer_when_no_room_admin_exists(): void
    {
        $roomOwner = User::factory()->create();
        $room = Room::factory()->create([
            'user_id' => $roomOwner->id,
            'everyone_can_book' => false,
        ]);
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'occupancy_option' => true,
        ]);
        $this->actingAs($roomOwner);

        $this->put(route('events.decline', $event))->assertRedirect();

        $event->refresh();
        $this->assertNull($event->room_id);
        $this->assertFalse($event->occupancy_option);
        $this->assertSame($room->id, $event->declined_room_id);
    }

    #[Test]
    public function admin_can_decline_a_confirmed_event_without_occupancy_option(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'occupancy_option' => false,
            'accepted' => true,
        ]);

        $this->put(route('events.decline', $event))->assertRedirect();

        $event->refresh();
        $this->assertNull($event->room_id);
        $this->assertFalse($event->accepted);
        $this->assertSame($room->id, $event->declined_room_id);
    }

    #[Test]
    public function room_admin_can_decline_a_confirmed_event_without_occupancy_option(): void
    {
        $roomAdmin = User::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'occupancy_option' => false,
        ]);
        $this->actingAs($roomAdmin);

        $this->put(route('events.decline', $event))->assertRedirect();

        $event->refresh();
        $this->assertNull($event->room_id);
        $this->assertSame($room->id, $event->declined_room_id);
    }

    #[Test]
    public function declining_an_event_without_room_returns_conflict(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['room_id' => null]);

        $this->put(route('events.decline', $event))->assertStatus(409);
    }

    #[Test]
    public function guest_cannot_answer_on_event(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('event.answer', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_update_series_events(): void
    {
        $event = Event::factory()->create();

        $this->patchJson(route('events.series.update', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_updating_non_series_event_series_is_noop(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_series' => false]);

        $response = $this->patchJson(route('events.series.update', $event), [
            'value' => 0,
            'calculationType' => 1,
            'type' => 1,
        ]);

        // Method early-returns without response data
        $response->assertSuccessful();
    }

    #[Test]
    public function series_update_moves_all_series_events_and_rejects_unknown_rooms(): void
    {
        $this->actingAsAdmin();
        $series = SeriesEvents::query()->create(['frequency_id' => 1, 'end_date' => '2026-12-31 00:00:00']);
        $room = Room::factory()->create();
        $events = collect(['2026-10-06', '2026-10-13'])->map(fn (string $day) => Event::factory()->create([
            'is_series' => true,
            'series_id' => $series->id,
            'room_id' => $room->id,
            'start_time' => $day . ' 10:00:00',
            'end_time' => $day . ' 12:00:00',
        ]));

        // Unbekannter Raum: vorher FK-Fehler mitten in der Schleife (Serie halb geändert).
        $this->patchJson(route('events.series.update', $events->first()), [
            'newRoomId' => 999999999,
            'calculationType' => 1,
            'value' => 1,
            'type' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('newRoomId');
        $this->assertSame('2026-10-06 10:00:00', $events->first()->fresh()->start_time->format('Y-m-d H:i:s'));

        $this->patchJson(route('events.series.update', $events->first()), [
            'newRoomId' => null,
            'calculationType' => 1,
            'value' => 1,
            'type' => 2,
        ])->assertSuccessful();

        $this->assertSame('2026-10-07 10:00:00', $events[0]->fresh()->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-14 12:00:00', $events[1]->fresh()->end_time->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function fields_a_dialog_does_not_send_stay_unchanged(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $eventType = EventType::factory()->create();
        $status = EventStatus::factory()->create();
        $property = EventProperty::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'event_status_id' => $status->id,
            'admission_time' => '18:30:00',
        ]);
        $event->eventProperties()->attach($property->id);

        // Antwort-Dialog / „Termine ohne Raum“ (Benachrichtigungen): ohne Status, Einlass, Eigenschaften
        $this->putJson(route('events.update', $event), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Antwort',
            'eventName' => 'Probe',
            'isOption' => false,
            'noNotifications' => true,
        ])->assertSuccessful();

        $event->refresh();
        $this->assertSame('Probe', $event->eventName);
        $this->assertSame($status->id, $event->event_status_id);
        $this->assertSame('18:30', substr((string) $event->admission_time, 0, 5));
        $this->assertSame([$property->id], $event->eventProperties()->pluck('event_properties.id')->all());
    }

    #[Test]
    public function an_explicit_null_project_id_removes_the_project(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        $eventType = EventType::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => Project::factory()->create()->id,
        ]);

        // Projekt-Chip „entfernen“ im Termin-Dialog: projectId muss als null ankommen – ein fehlendes
        // Feld lässt das Projekt seit der Teil-Update-Semantik bewusst stehen
        $this->putJson(route('events.update', $event), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Ohne Projekt',
            'isOption' => false,
            'projectId' => null,
            'projectName' => '',
            'noNotifications' => true,
        ])->assertSuccessful();

        $this->assertNull($event->fresh()->project_id);
    }
}
