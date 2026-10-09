<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Raumanfrage-Meldungen über den Lebenszyklus eines Termins: Wiederherstellen (Einzeln, Projekt, Serie) öffnet die
 * beim Löschen geschlossene Anfrage wieder, Massen-Löschen schließt auch Meldungen abgelehnter Termine, und
 * Massen-Raumwechsel (Serie, Multi-Edit, Bulk) geben die Anfrage an die Admins des neuen Raums weiter.
 */
final class RoomRequestLifecycleTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Echte Zustellung (Datenbank-Kanal): geprüft werden die gespeicherten Meldungen
        Notification::swap(new ChannelManager($this->app));
    }

    // ------------------------------------------------------------------ Wiederherstellen

    #[Test]
    public function restoring_a_project_reopens_the_closed_room_requests_in_place(): void
    {
        $this->actingAsAdmin();
        [$room, $roomAdmin] = $this->roomWithAdmin();
        $project = Project::factory()->create();
        $request = $this->openRequest($room, ['project_id' => $project->id]);
        $planned = $this->openRequest($room, ['project_id' => $project->id, 'is_planning' => true]);

        $this->deleteJson(route('event.bulk.multi-edit.delete'), ['eventIds' => [$request->id, $planned->id]])
            ->assertSuccessful();
        $this->assertSame(0, $this->openRequestsOf($roomAdmin, $request)->count());
        // Inzwischen neu ernannte Raumadmin hatte noch keine Meldung
        $newRoomAdmin = $this->recipient();
        $room->users()->attach($newRoomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $project->delete();

        $this->patch(route('projects.restore', $project->id))->assertRedirect(route('projects.trashed'));

        // Bestehende Meldung wieder offen – an Ort und Stelle, keine zweite (keine Flut bei vielen Terminen)
        $this->assertSame(1, $this->roomRequestsOf($roomAdmin, $request)->count());
        $reopened = $this->openRequestsOf($roomAdmin, $request)->sole();
        $this->assertSame(['show_in_calendar', 'accept', 'decline'], $reopened->data['buttons']);
        $this->assertArrayNotHasKey('isModified', $reopened->data);
        $this->assertNull($reopened->read_at);
        $this->assertSame(1, $this->openRequestsOf($newRoomAdmin, $request)->count());
        // Geplante Termine fragen erst beim Umstellen an
        $this->assertSame(0, $this->openRequestsOf($roomAdmin, $planned)->count());
        $this->assertSame(0, $this->openRequestsOf($newRoomAdmin, $planned)->count());
    }

    #[Test]
    public function restoring_a_series_reopens_the_room_request_of_every_occurrence(): void
    {
        $this->actingAsAdmin();
        [$room, $roomAdmin] = $this->roomWithAdmin();
        $series = SeriesEvents::query()->create([
            'frequency_id' => SeriesEvents::FREQUENCY_WEEKLY,
            'end_date' => '2026-12-31',
        ]);
        $events = collect([
            $this->openRequest($room, ['is_series' => true, 'series_id' => $series->id]),
            $this->openRequest($room, ['is_series' => true, 'series_id' => $series->id]),
        ]);
        $this->deleteJson(route('event.bulk.multi-edit.delete'), ['eventIds' => $events->pluck('id')->all()])
            ->assertSuccessful();

        $this->patchJson(route('events.series.restore', $series))->assertSuccessful();

        foreach ($events as $event) {
            $this->assertSame(1, $this->roomRequestsOf($roomAdmin, $event)->count());
            $this->assertSame(1, $this->openRequestsOf($roomAdmin, $event)->count());
        }
    }

    #[Test]
    public function restoring_a_project_scans_the_notifications_independently_of_the_number_of_requests(): void
    {
        $this->actingAsAdmin();
        [$room] = $this->roomWithAdmin();
        $secondRoomAdmin = $this->recipient();
        $room->users()->attach($secondRoomAdmin->id, ['is_admin' => true, 'can_request' => false]);

        $queriesForThree = $this->notificationQueriesWhileRestoring($this->trashedProjectWithRequests($room, 3));
        $queriesForTwelve = $this->notificationQueriesWhileRestoring($this->trashedProjectWithRequests($room, 12));

        // Vorher je Termin Wiederöffnen + Aufräumen + je Empfänger:in ein Scan (hier 4 je Termin)
        $this->assertSame($queriesForThree, $queriesForTwelve);
    }

    #[Test]
    public function moving_many_open_requests_scans_the_notifications_independently_of_their_number(): void
    {
        $this->actingAsAdmin();
        [$oldRoom] = $this->roomWithAdmin();
        [$newRoom] = $this->roomWithAdmin();
        $scans = [];
        foreach ([2, 6] as $count) {
            $requests = collect(range(1, $count))->map(fn (): Event => $this->notifiedOpenRequest($oldRoom));
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->postJson(route('events.bulk-multi-edit'), [
                'eventIds' => $requests->pluck('id')->all(),
                'selectedRoom' => ['id' => $newRoom->id],
            ])->assertSuccessful();
            $scans[$count] = $this->notificationSelects();
            DB::disableQueryLog();
            Event::query()->whereIn('id', $requests->pluck('id'))->update(['room_id' => $oldRoom->id]);
        }

        $this->assertSame($scans[2], $scans[6]);
    }

    #[Test]
    public function restoring_an_event_whose_room_is_in_the_trash_keeps_the_request_closed(): void
    {
        $this->actingAsAdmin();
        [$room, $roomAdmin] = $this->roomWithAdmin();
        $request = $this->notifiedOpenRequest($room);
        $this->deleteJson(route('event.bulk.multi-edit.delete'), ['eventIds' => [$request->id]])
            ->assertSuccessful();
        $room->delete();

        $this->patch(route('events.restore', $request->id))->assertRedirect();

        // Niemand kann die Anfrage beantworten – Annehmen/Ablehnen kämen sonst ohne Raum zurück
        $this->assertNotSoftDeleted('events', ['id' => $request->id]);
        $this->assertSame(1, $this->roomRequestsOf($roomAdmin, $request)->count());
        $this->assertSame(0, $this->openRequestsOf($roomAdmin, $request)->count());
    }

    #[Test]
    public function restoring_a_single_planning_event_sends_no_room_request(): void
    {
        $this->actingAsAdmin();
        [$room, $roomAdmin] = $this->roomWithAdmin();
        $planned = Event::factory()->create([
            'room_id' => $room->id,
            'occupancy_option' => true,
            'is_planning' => true,
        ]);
        $planned->delete();

        $this->patch(route('events.restore', $planned->id))->assertRedirect();

        $this->assertNotSoftDeleted('events', ['id' => $planned->id]);
        $this->assertSame(0, $this->roomRequestsOf($roomAdmin, $planned)->count());
    }

    #[Test]
    public function restoring_a_single_event_twice_keeps_one_room_request_per_admin(): void
    {
        $this->actingAsAdmin();
        [$room, $roomAdmin] = $this->roomWithAdmin();
        $request = $this->openRequest($room);

        for ($round = 0; $round < 2; $round++) {
            $this->deleteJson(route('event.bulk.multi-edit.delete'), ['eventIds' => [$request->id]])
                ->assertSuccessful();
            $this->patch(route('events.restore', $request->id))->assertRedirect();
        }

        $this->assertSame(1, $this->roomRequestsOf($roomAdmin, $request)->count());
        $this->assertSame(1, $this->openRequestsOf($roomAdmin, $request)->count());
    }

    // ------------------------------------------------------------------ Massen-Löschen

    #[Test]
    public function bulk_deleting_a_declined_event_removes_its_decline_notification(): void
    {
        $this->actingAsAdmin();
        $projectManager = $this->recipient();
        /** @var Room $declinedRoom */
        $declinedRoom = Room::factory()->create();
        $declined = Event::factory()->create([
            'room_id' => null,
            'declined_room_id' => $declinedRoom->id,
            'occupancy_option' => false,
        ]);
        $declineNotification = $this->storedNotification($projectManager, [
            'type' => NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST->value,
            'eventId' => $declined->id,
            'buttons' => ['change_request', 'event_delete'],
        ]);
        $otherEvent = Event::factory()->create(['room_id' => null, 'occupancy_option' => false]);
        $otherDeclineNotification = $this->storedNotification($projectManager, [
            'type' => NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST->value,
            'eventId' => $otherEvent->id,
            'buttons' => ['change_request', 'event_delete'],
        ]);

        // Weg aus „Termine ohne Raum“ (EventsWithoutRoomComponent)
        $this->deleteJson(route('event.bulk.multi-edit.delete'), ['eventIds' => [$declined->id]])
            ->assertSuccessful();

        $this->assertSoftDeleted('events', ['id' => $declined->id]);
        // Sonst führten Änderungsanfrage/Löschen auf einen Termin im Papierkorb (404)
        $this->assertDatabaseMissing('notifications', ['id' => $declineNotification]);
        $this->assertDatabaseHas('notifications', ['id' => $otherDeclineNotification]);
    }

    // ------------------------------------------------------------------ Massen-Raumwechsel

    #[Test]
    public function moving_a_series_of_open_requests_hands_the_requests_to_the_new_room(): void
    {
        $this->actingAsAdmin();
        [$oldRoom, $oldRoomAdmin] = $this->roomWithAdmin();
        [$newRoom, $newRoomAdmin] = $this->roomWithAdmin();
        $series = SeriesEvents::query()->create([
            'frequency_id' => SeriesEvents::FREQUENCY_WEEKLY,
            'end_date' => '2026-12-31',
        ]);
        $events = collect([
            $this->openRequest($oldRoom, ['is_series' => true, 'series_id' => $series->id]),
            $this->openRequest($oldRoom, ['is_series' => true, 'series_id' => $series->id]),
        ]);
        $events->each(fn (Event $event) => app(RoomRequestNotificationService::class)->notifyRoomAdmins($event));

        $this->patchJson(route('events.series.update', $events->first()), [
            'newRoomId' => $newRoom->id,
            'value' => 0,
        ])->assertSuccessful();

        foreach ($events as $event) {
            $this->assertSame($newRoom->id, $event->fresh()->room_id);
            // Genau eine Anfrage je Termin beim neuen Raum, keine mehr beim alten
            $this->assertSame(1, $this->openRequestsOf($newRoomAdmin, $event)->count());
            $this->assertSame(0, $this->openRequestsOf($oldRoomAdmin, $event)->count());
        }
    }

    #[Test]
    public function moving_open_requests_via_calendar_multi_edit_hands_the_requests_to_the_new_room(): void
    {
        $this->actingAsAdmin();
        [$oldRoom, $oldRoomAdmin] = $this->roomWithAdmin();
        [$newRoom, $newRoomAdmin] = $this->roomWithAdmin();
        $request = $this->notifiedOpenRequest($oldRoom);
        $planned = $this->openRequest($oldRoom, ['is_planning' => true]);

        $this->patchJson(route('multi-edit.save'), [
            'events' => [$request->id, $planned->id],
            'newRoomId' => $newRoom->id,
            'date' => '',
            'value' => 0,
        ])->assertSuccessful();

        $this->assertSame($newRoom->id, $request->fresh()->room_id);
        $this->assertSame(1, $this->openRequestsOf($newRoomAdmin, $request)->count());
        $this->assertSame(0, $this->openRequestsOf($oldRoomAdmin, $request)->count());
        $this->assertSame(0, $this->roomRequestsOf($newRoomAdmin, $planned)->count());
    }

    #[Test]
    public function moving_open_requests_via_bulk_multi_edit_hands_the_requests_to_the_new_room(): void
    {
        $this->actingAsAdmin();
        [$oldRoom, $oldRoomAdmin] = $this->roomWithAdmin();
        [$newRoom, $newRoomAdmin] = $this->roomWithAdmin();
        $request = $this->notifiedOpenRequest($oldRoom);

        $this->postJson(route('events.bulk-multi-edit'), [
            'eventIds' => [$request->id],
            'selectedRoom' => ['id' => $newRoom->id],
        ])->assertSuccessful();

        $this->assertSame($newRoom->id, $request->fresh()->room_id);
        $this->assertSame(1, $this->openRequestsOf($newRoomAdmin, $request)->count());
        $this->assertSame(0, $this->openRequestsOf($oldRoomAdmin, $request)->count());
    }

    #[Test]
    public function moving_an_open_request_via_single_bulk_update_hands_the_request_to_the_new_room(): void
    {
        $this->actingAsAdmin();
        [$oldRoom, $oldRoomAdmin] = $this->roomWithAdmin();
        [$newRoom, $newRoomAdmin] = $this->roomWithAdmin();
        $request = $this->notifiedOpenRequest($oldRoom);

        $this->patchJson(route('event.update.single.bulk', $request), [
            'data' => $this->bulkData($request, ['room' => ['id' => $newRoom->id]]),
        ])->assertSuccessful();

        $this->assertSame($newRoom->id, $request->fresh()->room_id);
        $this->assertSame(1, $this->openRequestsOf($newRoomAdmin, $request)->count());
        $this->assertSame(0, $this->openRequestsOf($oldRoomAdmin, $request)->count());
    }

    #[Test]
    public function an_unchanged_open_request_is_not_marked_as_modified_by_a_bulk_save(): void
    {
        $this->actingAsAdmin();
        [$room, $roomAdmin] = $this->roomWithAdmin();
        $request = $this->notifiedOpenRequest($room);

        $this->patchJson(route('event.update.single.bulk', $request), [
            'data' => $this->bulkData($request),
        ])->assertSuccessful();

        $this->assertArrayNotHasKey('isModified', $this->openRequestsOf($roomAdmin, $request)->sole()->data);
    }

    // ------------------------------------------------------------------ Helfer

    private function trashedProjectWithRequests(Room $room, int $count): Project
    {
        $project = Project::factory()->create();
        $requests = collect(range(1, $count))->map(fn (): Event => $this->openRequest(
            $room,
            ['project_id' => $project->id]
        ));
        $requests->each(fn (Event $event) => app(RoomRequestNotificationService::class)->notifyRoomAdmins($event));
        $this->deleteJson(route('event.bulk.multi-edit.delete'), ['eventIds' => $requests->pluck('id')->all()])
            ->assertSuccessful();
        $project->delete();

        return $project;
    }

    private function notificationQueriesWhileRestoring(Project $project): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->patch(route('projects.restore', $project->id))->assertRedirect(route('projects.trashed'));
        $queries = count(array_filter(
            DB::getQueryLog(),
            static fn (array $query): bool => str_contains($query['query'], '`notifications`')
        ));
        DB::disableQueryLog();

        // Alle Anfragen wieder offen
        foreach (Event::query()->where('project_id', $project->id)->get() as $event) {
            foreach ($event->room->users()->wherePivot('is_admin', true)->get() as $roomAdmin) {
                $this->assertSame(1, $this->openRequestsOf($roomAdmin, $event)->count());
            }
        }

        return $queries;
    }

    private function notificationSelects(): int
    {
        return count(array_filter(
            DB::getQueryLog(),
            static fn (array $query): bool => str_contains($query['query'], '`notifications`')
                && str_starts_with(strtolower(ltrim($query['query'])), 'select')
        ));
    }

    private function recipient(): User
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);

        return $user;
    }

    /**
     * @return array{Room, User}
     */
    private function roomWithAdmin(): array
    {
        $roomAdmin = $this->recipient();
        /** @var Room $room */
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);

        return [$room, $roomAdmin];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function openRequest(Room $room, array $attributes = []): Event
    {
        return Event::factory()->create(array_replace([
            'room_id' => $room->id,
            'occupancy_option' => true,
            'is_planning' => false,
            'is_series' => false,
            'start_time' => '2026-11-10 10:00:00',
            'end_time' => '2026-11-10 12:00:00',
            'allDay' => false,
        ], $attributes));
    }

    private function notifiedOpenRequest(Room $room): Event
    {
        $event = $this->openRequest($room);
        app(RoomRequestNotificationService::class)->notifyRoomAdmins($event);

        return $event;
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

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function roomRequestsOf(User $user, Event $event): Collection
    {
        return $user->notifications()->get()
            ->filter(static fn (DatabaseNotification $notification): bool =>
                ($notification->data['type'] ?? null) === NotificationEnum::NOTIFICATION_ROOM_REQUEST->value
                && (int) ($notification->data['eventId'] ?? 0) === (int) $event->id)
            ->values();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function openRequestsOf(User $user, Event $event): Collection
    {
        return $this->roomRequestsOf($user, $event)
            ->filter(static fn (DatabaseNotification $notification): bool =>
                !isset($notification->data['handledStatus']))
            ->values();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function storedNotification(User $user, array $data): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => RoomRequestNotification::class,
            'data' => $data + ['groupType' => 'ROOMS'],
        ]);

        return $id;
    }
}
