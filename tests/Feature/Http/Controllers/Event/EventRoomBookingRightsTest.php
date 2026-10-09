<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

/**
 * Raumrechte über alle Wege, auf denen Termine einen Raum belegen: Serien-Raumwechsel, Einzeltermin -> Serie,
 * Bulk-/Multi-Edit/Serien-Updates, is_planning über Bulk-Update und Autorisierung vor jeder Änderung.
 * Maßstab ist EventController::storeEvent (Termin-Dialog) bzw. authorizeBulkEventCreation (Bulk ohne Anfrage).
 */
final class EventRoomBookingRightsTest extends FeatureTestCase
{
    // ------------------------------------------------------------------ Serie: Raumwechsel „ganze Serie“

    #[Test]
    public function series_room_change_by_request_only_user_turns_every_moved_occurrence_into_a_room_request(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        [$targetRoom, $roomAdmin] = $this->roomWithAdmin();
        $events = $this->series($requester, Room::factory()->create(['everyone_can_book' => false]), 3);

        $this->putJson(
            route('events.update', $events->first()),
            $this->updatePayload($events->first(), ['roomId' => $targetRoom->id, 'seriesScope' => 'all'])
        )->assertSuccessful();

        foreach ($events as $event) {
            $event->refresh();
            $this->assertSame($targetRoom->id, $event->room_id);
            $this->assertTrue($event->occupancy_option, 'Mitverschobener Termin muss Raumanfrage sein');
            $this->assertFalse((bool) $event->accepted);
        }
        // Wie beim Einzeltermin: Raumadmins des Zielraums erfahren von jedem angefragten Termin
        $this->assertSame(3, $this->roomRequestsSentTo($roomAdmin));
    }

    #[Test]
    public function series_room_change_by_admin_of_the_target_room_keeps_all_occurrences_booked(): void
    {
        $roomAdmin = User::factory()->create();
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);
        $targetRoom->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $this->actingAs($roomAdmin);
        $events = $this->series($roomAdmin, Room::factory()->create(['everyone_can_book' => false]), 3);

        $this->putJson(
            route('events.update', $events->first()),
            $this->updatePayload($events->first(), ['roomId' => $targetRoom->id, 'seriesScope' => 'all'])
        )->assertSuccessful();

        foreach ($events as $event) {
            $event->refresh();
            $this->assertSame($targetRoom->id, $event->room_id);
            $this->assertFalse($event->occupancy_option);
        }
    }

    // ------------------------------------------------------------------ Einzeltermin -> Serie

    #[Test]
    public function turning_a_booked_event_into_a_series_as_request_only_user_rolls_out_room_requests(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        [$room, $roomAdmin] = $this->roomWithAdmin();
        $event = $this->bookedEvent($requester, $room);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_DAILY,
            'seriesOccurrenceCount' => 3,
        ]))->assertSuccessful();

        $event->refresh();
        $this->assertTrue($event->is_series);
        // Der bereits gebuchte Termin bleibt gebucht, die neuen sind nur angefragt
        $this->assertFalse($event->occupancy_option);
        $rolledOut = Event::query()->where('series_id', $event->series_id)->whereKeyNot($event->id)->get();
        $this->assertCount(2, $rolledOut);
        $this->assertSame([true], $rolledOut->pluck('occupancy_option')->unique()->values()->all());
        $this->assertSame(2, $this->roomRequestsSentTo($roomAdmin));
    }

    #[Test]
    public function turning_an_event_into_a_series_without_any_room_right_is_forbidden_and_changes_nothing(): void
    {
        $creator = User::factory()->create();
        $this->actingAs($creator);
        $event = $this->bookedEvent($creator, Room::factory()->create(['everyone_can_book' => false]));
        $originalName = $event->eventName;

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'eventName' => 'Geändert',
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_DAILY,
            'seriesOccurrenceCount' => 3,
        ]))->assertForbidden();

        $event->refresh();
        $this->assertFalse($event->is_series);
        $this->assertSame($originalName, $event->eventName);
        $this->assertSame(1, Event::query()->count());
    }

    #[Test]
    public function turning_an_event_into_a_series_with_booking_right_rolls_out_booked_events(): void
    {
        $roomAdmin = User::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $this->actingAs($roomAdmin);
        $event = $this->bookedEvent($roomAdmin, $room);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_DAILY,
            'seriesOccurrenceCount' => 3,
        ]))->assertSuccessful();

        $series = Event::query()->where('series_id', $event->fresh()->series_id)->get();
        $this->assertCount(3, $series);
        $this->assertSame([false], $series->pluck('occupancy_option')->unique()->values()->all());
    }

    #[Test]
    public function editing_a_series_with_unchanged_definition_needs_no_room_right(): void
    {
        // Darf bearbeiten (Ersteller:in), hat aber im Raum weder Anfrage- noch Buchungsrecht
        $creator = User::factory()->create();
        $this->actingAs($creator);
        $events = $this->series($creator, Room::factory()->create(['everyone_can_book' => false]), 3, '2026-11-24');

        // Der Dialog schickt bei „alle“ die (unveränderte) Seriendefinition immer mit
        $this->putJson(route('events.update', $events->first()), $this->updatePayload($events->first(), [
            'description' => 'Neue Beschreibung',
            'seriesScope' => 'all',
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_WEEKLY,
            'seriesEndDate' => '2026-11-24',
        ]))->assertSuccessful();

        $this->assertSame(3, Event::query()->where('series_id', $events->first()->series_id)->count());
        foreach ($events as $event) {
            $this->assertSame('Neue Beschreibung', $event->fresh()->description);
        }
    }

    #[Test]
    public function extending_a_series_uses_the_room_of_the_template_occurrence(): void
    {
        // Raumadmin von Raum A, in Raum B nur Anfragerecht – die angehängten Termine kopieren den letzten
        // Termin der Serie (Raum B) und dürfen dort nicht fest gebucht werden
        $user = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $roomA = Room::factory()->create(['everyone_can_book' => false]);
        $roomA->users()->attach($user->id, ['is_admin' => true, 'can_request' => false]);
        $roomB = Room::factory()->create(['everyone_can_book' => false]);
        $events = $this->series($user, $roomA, 3, '2026-11-24');
        $events->last()->update(['room_id' => $roomB->id, 'is_series_exception' => true]);

        $this->putJson(route('events.update', $events->first()), $this->updatePayload($events->first(), [
            'seriesScope' => 'all',
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_WEEKLY,
            'seriesEndDate' => '2026-12-08',
        ]))->assertSuccessful();

        $appended = Event::query()
            ->where('series_id', $events->first()->series_id)
            ->whereKeyNot($events->pluck('id')->all())
            ->get();
        $this->assertCount(2, $appended);
        $this->assertSame([$roomB->id], $appended->pluck('room_id')->unique()->values()->all());
        $this->assertSame([true], $appended->pluck('occupancy_option')->unique()->values()->all());
    }

    #[Test]
    public function series_room_change_with_rebuild_notifies_only_for_occurrences_that_remain(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        [$targetRoom, $roomAdmin] = $this->roomWithAdmin();
        $events = $this->series($requester, Room::factory()->create(['everyone_can_book' => false]), 3, '2026-11-24');

        // Raumwechsel + Turnuswechsel (wöchentlich -> täglich bis 12.11.): die beiden Geschwister gehen in den
        // Papierkorb, 11.11. und 12.11. entstehen neu – Anfragen nur für den bearbeiteten und die neuen Termine
        $this->putJson(route('events.update', $events->first()), $this->updatePayload($events->first(), [
            'roomId' => $targetRoom->id,
            'seriesScope' => 'all',
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_DAILY,
            'seriesEndDate' => '2026-11-12',
        ]))->assertSuccessful();

        $this->assertSoftDeleted('events', ['id' => $events[1]->id]);
        $this->assertSoftDeleted('events', ['id' => $events[2]->id]);
        $active = Event::query()->where('series_id', $events->first()->series_id)->get();
        $this->assertCount(3, $active);
        $this->assertSame([true], $active->pluck('occupancy_option')->unique()->values()->all());
        $this->assertSame(3, $this->roomRequestsSentTo($roomAdmin));
    }

    #[Test]
    public function deferred_sibling_room_requests_are_sent_even_if_rolling_out_the_series_fails(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        [$targetRoom, $roomAdmin] = $this->roomWithAdmin();
        $events = $this->series($requester, Room::factory()->create(['everyone_can_book' => false]), 3, '2026-11-24');
        // Das Anlegen der angehängten Termine scheitert (erste Event-Erstellung im Request ist das Ausrollen)
        Event::creating(static function (): void {
            throw new RuntimeException('Ausrollen gescheitert');
        });

        // Raumwechsel (Geschwister werden Anfragen) + Verlängern, das scheitert
        $this->putJson(route('events.update', $events->first()), $this->updatePayload($events->first(), [
            'roomId' => $targetRoom->id,
            'seriesScope' => 'all',
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_WEEKLY,
            'seriesEndDate' => '2026-12-08',
        ]))->assertServerError();

        foreach ($events as $event) {
            $this->assertTrue($event->fresh()->occupancy_option);
        }
        // Bearbeiteter Termin + beide Geschwister: die Raumadmins erfahren trotzdem von allen Anfragen
        $this->assertSame(3, $this->roomRequestsSentTo($roomAdmin));
    }

    #[Test]
    public function siblings_already_turned_into_requests_are_notified_when_propagation_fails_midway(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        [$targetRoom, $roomAdmin] = $this->roomWithAdmin();
        $events = $this->series($requester, Room::factory()->create(['everyone_can_book' => false]), 3);
        $failingSiblingId = $events[2]->id;
        // Das Speichern des letzten Geschwisters scheitert – das vorherige ist schon als Anfrage gespeichert
        Event::updating(static function (Event $event) use ($failingSiblingId): void {
            if ($event->id === $failingSiblingId) {
                throw new RuntimeException('Speichern gescheitert');
            }
        });

        $this->putJson(
            route('events.update', $events->first()),
            $this->updatePayload($events->first(), ['roomId' => $targetRoom->id, 'seriesScope' => 'all'])
        )->assertServerError();

        $this->assertTrue($events[1]->fresh()->occupancy_option);
        // Bearbeiteter Termin + das bereits umgestellte Geschwister
        $this->assertSame(2, $this->roomRequestsSentTo($roomAdmin));
    }

    #[Test]
    public function extending_a_series_whose_last_occurrence_has_no_room_uses_the_last_occurrence_with_room(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $events = $this->series($requester, $room, 3, '2026-11-24');
        // Letzter Termin abgesagt (Raum entzogen)
        $events->last()->update(['room_id' => null, 'declined_room_id' => $room->id]);

        $this->putJson(route('events.update', $events->first()), $this->updatePayload($events->first(), [
            'seriesScope' => 'all',
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_WEEKLY,
            'seriesEndDate' => '2026-12-08',
        ]))->assertSuccessful();

        $appended = Event::query()
            ->where('series_id', $events->first()->series_id)
            ->whereKeyNot($events->pluck('id')->all())
            ->get();
        $this->assertCount(2, $appended);
        $this->assertSame([$room->id], $appended->pluck('room_id')->unique()->values()->all());
        $this->assertSame([true], $appended->pluck('occupancy_option')->unique()->values()->all());
    }

    #[Test]
    public function admin_accepting_a_request_while_turning_it_into_a_series_rolls_out_booked_events(): void
    {
        $admin = $this->actingAsAdmin();
        $event = $this->bookedEvent($admin, Room::factory()->create(['everyone_can_book' => false]));
        $event->update(['occupancy_option' => true]);

        // isOption=false nimmt die Anfrage an; die neuen Termine dürfen nicht aus dem alten Status Anfragen werden
        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'isOption' => false,
            'is_series' => true,
            'seriesFrequency' => SeriesEvents::FREQUENCY_DAILY,
            'seriesOccurrenceCount' => 3,
        ]))->assertSuccessful();

        $series = Event::query()->where('series_id', $event->fresh()->series_id)->get();
        $this->assertCount(3, $series);
        $this->assertSame([false], $series->pluck('occupancy_option')->unique()->values()->all());
    }

    // ------------------------------------------------------------------ Raumwechsel Einzeltermin

    #[Test]
    public function room_change_without_any_room_right_still_becomes_a_room_request_instead_of_forbidden(): void
    {
        $creator = User::factory()->create();
        $this->actingAs($creator);
        $event = $this->bookedEvent($creator, Room::factory()->create(['everyone_can_book' => false]));
        [$targetRoom] = $this->roomWithAdmin();

        $this->putJson(route('events.update', $event), $this->updatePayload($event, ['roomId' => $targetRoom->id]))
            ->assertSuccessful();

        $event->refresh();
        $this->assertSame($targetRoom->id, $event->room_id);
        $this->assertTrue($event->occupancy_option);
    }

    #[Test]
    public function moving_a_planning_event_needs_the_planning_calendar_booking_right(): void
    {
        // „Termine fest planen“ bucht nur reguläre Termine direkt – geplante wie beim Anlegen nur mit
        // „Im Planungskalender fest planen“
        $planner = $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST]);
        $event = $this->bookedEvent($planner, Room::factory()->create(['everyone_can_book' => false]));
        $event->update(['is_planning' => true]);
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, ['roomId' => $targetRoom->id]))
            ->assertSuccessful();

        $event->refresh();
        $this->assertSame($targetRoom->id, $event->room_id);
        $this->assertTrue($event->occupancy_option);
    }

    // ------------------------------------------------------------------ Bulk / Multi-Edit / Serien-Update

    #[Test]
    public function request_only_creator_cannot_move_an_event_via_single_bulk_update(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $event = $this->bookedEvent($requester, Room::factory()->create(['everyone_can_book' => false]));
        $originalRoomId = $event->room_id;
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);

        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['room' => ['id' => $targetRoom->id]]),
        ])->assertForbidden();

        $this->assertSame($originalRoomId, $event->fresh()->room_id);
    }

    #[Test]
    public function request_only_creator_can_still_edit_an_event_via_single_bulk_update_without_room_change(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $event = $this->bookedEvent($requester, Room::factory()->create(['everyone_can_book' => false]));

        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['name' => 'Neuer Name']),
        ])->assertSuccessful();

        $this->assertSame('Neuer Name', $event->fresh()->eventName);
    }

    #[Test]
    public function admin_of_the_target_room_can_move_an_event_via_single_bulk_update(): void
    {
        $roomAdmin = User::factory()->create();
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);
        $targetRoom->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $this->actingAs($roomAdmin);
        $event = $this->bookedEvent($roomAdmin, Room::factory()->create(['everyone_can_book' => false]));

        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['room' => ['id' => $targetRoom->id]]),
        ])->assertSuccessful();

        $this->assertSame($targetRoom->id, $event->fresh()->room_id);
    }

    #[Test]
    public function request_only_creator_cannot_move_events_via_bulk_multi_edit(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $event = $this->bookedEvent($requester, Room::factory()->create(['everyone_can_book' => false]));
        $originalRoomId = $event->room_id;
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('events.bulk-multi-edit'), [
            'eventIds' => [$event->id],
            'selectedRoom' => ['id' => $targetRoom->id],
        ])->assertForbidden();

        $this->assertSame($originalRoomId, $event->fresh()->room_id);
    }

    #[Test]
    public function request_only_creator_cannot_move_events_via_calendar_multi_edit(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $event = $this->bookedEvent($requester, Room::factory()->create(['everyone_can_book' => false]));
        $originalRoomId = $event->room_id;
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);

        $this->patchJson(route('multi-edit.save'), [
            'events' => [$event->id],
            'newRoomId' => $targetRoom->id,
            'date' => '',
            'value' => 0,
        ])->assertForbidden();

        $this->assertSame($originalRoomId, $event->fresh()->room_id);
    }

    #[Test]
    public function request_only_creator_cannot_duplicate_booked_events_via_calendar_multi_duplicate(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $event = $this->bookedEvent($requester, Room::factory()->create(['everyone_can_book' => false]));

        $this->patchJson(route('multi-duplicate.save'), [
            'events' => [$event->id],
            'date' => '2026-12-01',
        ])->assertForbidden();

        $this->assertSame(1, Event::query()->count());
    }

    #[Test]
    public function request_only_creator_cannot_move_a_series_via_series_update(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $events = $this->series($requester, $room, 2);
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);

        $this->patchJson(route('events.series.update', $events->first()), [
            'newRoomId' => $targetRoom->id,
            'value' => 0,
        ])->assertForbidden();

        foreach ($events as $event) {
            $this->assertSame($room->id, $event->fresh()->room_id);
        }
    }

    #[Test]
    public function single_bulk_update_without_room_keeps_the_room(): void
    {
        $admin = $this->actingAsAdmin();
        $event = $this->bookedEvent($admin, Room::factory()->create());
        $roomId = $event->room_id;

        // Vorher 500 (room.id auf null) – eine Bulk-Zeile ohne Raumobjekt entfernt den Raum nicht
        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['name' => 'Ohne Raumobjekt', 'room' => null]),
        ])->assertSuccessful();

        $event->refresh();
        $this->assertSame($roomId, $event->room_id);
        $this->assertSame('Ohne Raumobjekt', $event->eventName);
    }

    #[Test]
    public function multi_cell_create_rejects_rooms_in_the_trash(): void
    {
        $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST]);
        $trashedRoom = Room::factory()->create();
        $trashedRoom->delete();

        $this->postJson(route('events.multi-cell.create'), [
            'cells' => [['day' => '2026-11-10', 'room_id' => $trashedRoom->id]],
            'event_type_id' => Event::factory()->create()->event_type_id,
        ])->assertUnprocessable()->assertJsonValidationErrors('cells.0.room_id');
    }

    #[Test]
    public function duplicating_an_event_whose_room_is_in_the_trash_requires_a_new_room(): void
    {
        $admin = $this->actingAsAdmin();
        $room = Room::factory()->create();
        $event = $this->bookedEvent($admin, $room);
        $room->delete();

        // Kopie im gelöschten Raum wäre ein Geistertermin (vgl. duplicateEventsToCells)
        $this->patchJson(route('multi-duplicate.save'), [
            'events' => [$event->id],
            'date' => '2026-12-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('newRoomId');

        $this->assertSame(1, Event::query()->count());
    }

    #[Test]
    public function duplicating_an_event_without_room_needs_the_right_to_create_events_without_room(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $event = $this->bookedEvent($requester, Room::factory()->create());
        $event->update(['room_id' => null]);

        $this->patchJson(route('multi-duplicate.save'), [
            'events' => [$event->id],
            'date' => '2026-12-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('roomId');

        $this->assertSame(1, Event::query()->count());
    }

    #[Test]
    public function duplicating_an_event_without_room_works_with_the_right_to_create_events_without_room(): void
    {
        $planner = $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST]);
        $event = $this->bookedEvent($planner, Room::factory()->create());
        $event->update(['room_id' => null]);

        $this->patchJson(route('multi-duplicate.save'), [
            'events' => [$event->id],
            'date' => '2026-12-01',
        ])->assertSuccessful();

        $this->assertSame(2, Event::query()->whereNull('room_id')->count());
    }

    #[Test]
    public function request_only_creator_can_move_an_event_to_another_day_in_the_same_room(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $event = $this->bookedEvent($requester, $room);

        $this->postJson(route('events.multi-cell.move'), [
            'events' => [$event->id],
            'cell' => ['day' => '2026-11-12', 'room_id' => $room->id],
        ])->assertSuccessful();

        $event->refresh();
        $this->assertSame('2026-11-12 10:00', $event->start_time->format('Y-m-d H:i'));
        $this->assertSame($room->id, $event->room_id);
    }

    #[Test]
    public function request_only_creator_cannot_move_an_event_into_another_room_via_cell_move(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $event = $this->bookedEvent($requester, $room);
        $targetRoom = Room::factory()->create(['everyone_can_book' => false]);

        $this->postJson(route('events.multi-cell.move'), [
            'events' => [$event->id],
            'cell' => ['day' => '2026-11-12', 'room_id' => $targetRoom->id],
        ])->assertForbidden();

        $this->assertSame($room->id, $event->fresh()->room_id);
    }

    #[Test]
    public function moving_an_open_room_request_to_another_day_updates_the_room_request(): void
    {
        $admin = $this->actingAsAdmin();
        [$room, $roomAdmin] = $this->roomWithAdmin();
        $event = $this->bookedEvent($admin, $room);
        $event->update(['occupancy_option' => true]);

        $this->postJson(route('events.multi-cell.move'), [
            'events' => [$event->id],
            'cell' => ['day' => '2026-11-12', 'room_id' => $room->id],
        ])->assertSuccessful();

        // Die (hier neu angelegte bzw. sonst aktualisierte) Anfrage zeigt das neue Datum
        $this->assertSame(1, $this->roomRequestsSentTo($roomAdmin));
    }

    #[Test]
    public function moving_an_open_room_request_into_another_room_notifies_the_new_room_admins(): void
    {
        $admin = $this->actingAsAdmin();
        $event = $this->bookedEvent($admin, Room::factory()->create(['everyone_can_book' => false]));
        $event->update(['occupancy_option' => true]);
        [$targetRoom, $roomAdmin] = $this->roomWithAdmin();

        $this->postJson(route('events.multi-cell.move'), [
            'events' => [$event->id],
            'cell' => ['day' => '2026-11-12', 'room_id' => $targetRoom->id],
        ])->assertSuccessful();

        $this->assertSame($targetRoom->id, $event->fresh()->room_id);
        $this->assertSame(1, $this->roomRequestsSentTo($roomAdmin));
    }

    // ------------------------------------------------------------------ is_planning über Bulk-Update

    #[Test]
    public function bulk_update_ignores_conversion_to_planning_without_planning_calendar_permission(): void
    {
        $planner = $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST]);
        $event = $this->bookedEvent($planner, Room::factory()->create());

        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['name' => 'Umbenannt', 'is_planning' => true]),
        ])->assertSuccessful();

        $event->refresh();
        $this->assertFalse((bool) $event->is_planning);
        $this->assertSame('Umbenannt', $event->eventName);
    }

    #[Test]
    public function bulk_update_converts_to_planning_with_planning_calendar_permission(): void
    {
        $planner = $this->actingAsUserWith([
            PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST,
            PermissionEnum::CAN_EDIT_PLANNING_CALENDAR,
        ]);
        $event = $this->bookedEvent($planner, Room::factory()->create());

        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['is_planning' => true]),
        ])->assertSuccessful();

        $this->assertTrue((bool) $event->fresh()->is_planning);
    }

    #[Test]
    public function bulk_update_never_confirms_a_planning_event(): void
    {
        $admin = $this->actingAsAdmin();
        $event = $this->bookedEvent($admin, Room::factory()->create());
        $event->update(['is_planning' => true]);

        // Bestätigen läuft nur über den Verifizierungsablauf (EventVerificationService)
        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['is_planning' => false]),
        ])->assertSuccessful();

        $this->assertTrue((bool) $event->fresh()->is_planning);
    }

    // ------------------------------------------------------------------ Erst autorisieren, dann ändern

    #[Test]
    public function calendar_multi_edit_changes_nothing_when_one_selected_event_is_forbidden(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $ownEvent = $this->bookedEvent($requester, Room::factory()->create());
        $foreignEvent = Event::factory()->create();
        $originalStart = $ownEvent->start_time->format('Y-m-d H:i');

        $this->patchJson(route('multi-edit.save'), [
            'events' => [$ownEvent->id, $foreignEvent->id],
            'date' => '2026-12-01',
        ])->assertForbidden();

        $this->assertSame($originalStart, $ownEvent->fresh()->start_time->format('Y-m-d H:i'));
    }

    #[Test]
    public function calendar_multi_delete_deletes_nothing_when_one_selected_event_is_forbidden(): void
    {
        $requester = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $ownEvent = $this->bookedEvent($requester, Room::factory()->create());
        $foreignEvent = Event::factory()->create();

        $this->postJson(route('multi-edit.delete'), [
            'events' => [$ownEvent->id, $foreignEvent->id],
        ])->assertForbidden();

        $this->assertNotSoftDeleted('events', ['id' => $ownEvent->id]);
        $this->assertNotSoftDeleted('events', ['id' => $foreignEvent->id]);
    }

    #[Test]
    public function calendar_multi_duplicate_copies_nothing_when_one_selected_event_is_forbidden(): void
    {
        $roomAdmin = User::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $this->actingAs($roomAdmin);
        $ownEvent = $this->bookedEvent($roomAdmin, $room);
        $foreignEvent = Event::factory()->create();

        $this->patchJson(route('multi-duplicate.save'), [
            'events' => [$ownEvent->id, $foreignEvent->id],
            'date' => '2026-12-01',
        ])->assertForbidden();

        $this->assertSame(2, Event::query()->count());
    }

    // ------------------------------------------------------------------ Benachrichtigungen

    #[Test]
    public function deleting_via_notification_removes_the_notifications_of_that_event_only(): void
    {
        $admin = $this->actingAsAdmin();
        $otherRecipient = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $key = Str::random(15);
        $own = $this->notificationFor($admin, ['notificationKey' => $key, 'eventId' => $event->id]);
        $foreign = $this->notificationFor($otherRecipient, ['notificationKey' => $key, 'eventId' => $event->id]);
        $ownOtherEvent = $this->notificationFor($admin, ['notificationKey' => $key, 'eventId' => $otherEvent->id]);

        $this->postJson(route('events.delete.by.notification', $event), ['notificationKey' => $key])
            ->assertSuccessful();

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        // Auch die Meldung der anderen Empfängerin zum gelöschten Termin verschwindet (sonst Klick -> 404) …
        $this->assertDatabaseMissing('notifications', ['id' => $own]);
        $this->assertDatabaseMissing('notifications', ['id' => $foreign]);
        // … eine Meldung mit gleichem Schlüssel zu einem anderen Termin bleibt
        $this->assertDatabaseHas('notifications', ['id' => $ownOtherEvent]);
    }

    #[Test]
    public function room_request_notifications_of_many_deleted_events_are_closed_in_one_go(): void
    {
        $roomAdmin = User::factory()->create();
        [$first, $second, $untouched] = Event::factory()->count(3)->create(['occupancy_option' => true])->all();
        $firstRequest = $this->notificationFor($roomAdmin, [
            'type' => NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
            'eventId' => $first->id,
        ]);
        $secondRequest = $this->notificationFor($roomAdmin, [
            'type' => NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
            'eventId' => $second->id,
        ]);
        $untouchedRequest = $this->notificationFor($roomAdmin, [
            'type' => NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
            'eventId' => $untouched->id,
        ]);
        $upsert = $this->notificationFor($roomAdmin, [
            'type' => NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST->value,
            'eventId' => $first->id,
        ]);
        $withoutEvent = $this->notificationFor($roomAdmin, [
            'type' => NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
            'eventId' => null,
        ]);

        app(NotificationService::class)->closeRoomRequestNotificationsForDeletedEvents(
            [$first->id, $second->id],
            'deleted'
        );

        $notifications = $roomAdmin->notifications()->get()->keyBy('id');
        $this->assertSame('deleted', $notifications[$firstRequest]->data['handledStatus']);
        $this->assertSame([], $notifications[$firstRequest]->data['buttons']);
        $this->assertSame('deleted', $notifications[$secondRequest]->data['handledStatus']);
        $this->assertArrayNotHasKey('handledStatus', $notifications[$untouchedRequest]->data);
        $this->assertArrayNotHasKey('handledStatus', $notifications[$withoutEvent]->data);
        $this->assertFalse($notifications->has($upsert));
    }

    // ------------------------------------------------------------------ Helfer

    /**
     * @return array{Room, User}
     */
    private function roomWithAdmin(): array
    {
        $roomAdmin = User::factory()->create();
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);

        return [$room, $roomAdmin];
    }

    private function bookedEvent(User $creator, Room $room): Event
    {
        return Event::factory()->create([
            'user_id' => $creator->id,
            'room_id' => $room->id,
            'occupancy_option' => false,
            'is_series' => false,
            'start_time' => '2026-11-10 10:00:00',
            'end_time' => '2026-11-10 12:00:00',
            'allDay' => false,
        ]);
    }

    /**
     * Fest gebuchte Wochenserie ab Di 10.11.2026.
     *
     * @return Collection<int, Event>
     */
    private function series(User $creator, Room $room, int $count, string $endDate = '2026-12-31'): Collection
    {
        $series = SeriesEvents::query()->create([
            'frequency_id' => SeriesEvents::FREQUENCY_WEEKLY,
            'end_date' => $endDate,
        ]);

        return collect(range(0, $count - 1))->map(fn (int $week): Event => Event::factory()->create([
            'user_id' => $creator->id,
            'room_id' => $room->id,
            'occupancy_option' => false,
            'accepted' => true,
            'is_series' => true,
            'series_id' => $series->id,
            'start_time' => Carbon::parse('2026-11-10 10:00:00')->addWeeks($week)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::parse('2026-11-10 12:00:00')->addWeeks($week)->format('Y-m-d H:i:s'),
            'allDay' => false,
        ]));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Event $event, array $overrides = []): array
    {
        return array_replace([
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $event->event_type_id,
            'roomId' => $event->room_id,
            'title' => $event->name,
            'eventName' => $event->eventName,
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'isPlanning' => false,
            'noNotifications' => true,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function bulkData(Event $event, array $overrides = []): array
    {
        return array_replace([
            'name' => $event->eventName,
            'day' => '2026-11-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'type' => ['id' => $event->event_type_id],
            'room' => ['id' => $event->room_id],
        ], $overrides);
    }

    private function roomRequestsSentTo(User $roomAdmin): int
    {
        return Notification::sent($roomAdmin, RoomRequestNotification::class)
            ->filter(fn (RoomRequestNotification $notification): bool =>
                $notification->toArray()->type === NotificationEnum::NOTIFICATION_ROOM_REQUEST)
            ->count();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function notificationFor(User $user, array $data): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => RoomRequestNotification::class,
            'data' => $data + [
                'type' => NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
                'groupType' => 'ROOMS',
                'buttons' => ['accept', 'decline'],
            ],
        ]);

        return $id;
    }
}
