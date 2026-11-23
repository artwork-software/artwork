<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Event\Services\SeriesEventsService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Jobs\SendNotificationMailJob;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

/**
 * Kundenfeedback 18.11.2026: Änderungen an bestätigten Terminen durch Personen ohne Direktbuchrecht
 * (EventChangeApprovalService), Rücksprung bei abgelehnter Änderung, „angefragt von“ und die Empfänger der Antwort.
 */
final class RoomRequestChangeApprovalTest extends FeatureTestCase
{
    private Room $room;

    private User $roomAdmin;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();
        // Echte Zustellung (Datenbank-Kanal): geprüft werden die gespeicherten Meldungen
        Notification::swap(new ChannelManager($this->app));

        $this->roomAdmin = $this->recipient();
        $this->room = Room::factory()->create(['everyone_can_book' => false]);
        $this->room->users()->attach($this->roomAdmin->id, ['is_admin' => true, 'can_request' => false]);
        $this->requester = $this->recipient();
    }

    // ------------------------------------------------------------------ Termin-Dialog

    #[Test]
    public function shortening_within_the_confirmed_booking_keeps_the_event_booked(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedAllDayEvent();

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-20 10:00',
            'end' => '2026-11-20 14:00',
            'allDay' => false,
        ]))->assertSuccessful()->assertJsonPath('room_request', 'none');

        $event->refresh();
        $this->assertFalse($event->occupancy_option);
        $this->assertSame($this->room->id, $event->approved_room_id);
        $this->assertSame('2026-11-20 00:00:00', $event->approved_start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-20 23:59:00', $event->approved_end_time->format('Y-m-d H:i:s'));
        $this->assertSame(0, $this->roomRequestsOf($this->roomAdmin, $event)->count());
    }

    #[Test]
    public function extending_back_to_the_confirmed_booking_needs_no_new_request(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedAllDayEvent();
        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-20 10:00',
            'end' => '2026-11-20 14:00',
            'allDay' => false,
        ]))->assertSuccessful();

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-20 00:00',
            'end' => '2026-11-20 23:59',
        ]))->assertSuccessful()->assertJsonPath('room_request', 'none');

        $event->refresh();
        $this->assertFalse($event->occupancy_option);
        // Wieder genau die bestätigte Belegung: nichts mehr gesondert zu merken
        $this->assertNull($event->approved_room_id);
        $this->assertSame(0, $this->roomRequestsOf($this->roomAdmin, $event)->count());
    }

    #[Test]
    public function moving_to_another_day_turns_the_change_into_a_room_request(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedAllDayEvent();

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-22 00:00',
            'end' => '2026-11-22 23:59',
        ]))->assertSuccessful()->assertJsonPath('room_request', 'created');

        $event->refresh();
        $this->assertTrue($event->occupancy_option);
        $this->assertSame($this->requester->id, $event->requested_by_id);
        $this->assertSame('2026-11-20 00:00:00', $event->approved_start_time->format('Y-m-d H:i:s'));
        $this->assertSame(1, $this->openRequestsOf($this->roomAdmin, $event)->count());
    }

    /**
     * Wer eine Änderung, die zur Raumanfrage wurde, wieder auf die bestätigte Belegung zurücknimmt, hat
     * wieder einen fest gebuchten Termin: es gibt nichts mehr zu beantworten.
     */
    #[Test]
    public function taking_a_change_request_back_to_the_confirmed_booking_books_the_event_again(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->changeRequest($this->requester);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-20 00:00',
            'end' => '2026-11-20 23:59',
        ]))->assertSuccessful()->assertJsonPath('room_request', 'none');

        $event->refresh();
        $this->assertFalse($event->occupancy_option);
        $this->assertNull($event->approved_room_id);
        $this->assertSame($this->requester->id, $event->requested_by_id);
    }

    /**
     * Eine Änderungsanfrage, die weiter von der bestätigten Belegung abweicht, bleibt offen.
     */
    #[Test]
    public function changing_a_change_request_to_another_deviating_slot_keeps_it_open(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->changeRequest($this->requester);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-23 00:00',
            'end' => '2026-11-23 23:59',
        ]))->assertSuccessful()->assertJsonPath('room_request', 'pending');

        $event->refresh();
        $this->assertTrue($event->occupancy_option);
        $this->assertSame('2026-11-20 00:00:00', $event->approved_start_time->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function extending_beyond_the_confirmed_booking_turns_the_change_into_a_room_request(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00');

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-20 10:00',
            'end' => '2026-11-20 13:00',
        ]))->assertSuccessful()->assertJsonPath('room_request', 'created');

        $this->assertTrue($event->fresh()->occupancy_option);
    }

    #[Test]
    public function renaming_a_confirmed_event_keeps_it_booked(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00');

        $this->putJson(route('events.update', $event), $this->updatePayload($event, ['eventName' => 'Neuer Name']))
            ->assertSuccessful()
            ->assertJsonPath('room_request', 'none');

        $this->assertFalse($event->fresh()->occupancy_option);
        $this->assertSame(0, $this->roomRequestsOf($this->roomAdmin, $event)->count());
    }

    #[Test]
    public function editing_an_open_request_reports_it_as_pending(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-21 10:00',
            'end' => '2026-11-21 12:00',
        ]))->assertSuccessful()->assertJsonPath('room_request', 'pending');

        $event->refresh();
        $this->assertTrue($event->occupancy_option);
        // Eine erstmalige Anfrage hat keine bestätigte Belegung, auf die eine Absage zurückspringen könnte
        $this->assertNull($event->approved_room_id);
    }

    #[Test]
    public function room_admin_changing_the_time_keeps_the_event_booked_and_confirms_the_new_slot(): void
    {
        $this->actingAs($this->roomAdmin);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', [
            'approved_room_id' => $this->room->id,
            'approved_start_time' => '2026-11-20 08:00:00',
            'approved_end_time' => '2026-11-20 18:00:00',
        ]);

        $this->putJson(route('events.update', $event), $this->updatePayload($event, [
            'start' => '2026-11-25 10:00',
            'end' => '2026-11-25 12:00',
        ]))->assertSuccessful()->assertJsonPath('room_request', 'none');

        $event->refresh();
        $this->assertFalse($event->occupancy_option);
        $this->assertNull($event->approved_room_id);
    }

    // ------------------------------------------------------------------ Antwort der Raumadmins

    #[Test]
    public function declining_a_change_request_restores_the_confirmed_booking(): void
    {
        $creator = $this->recipient();
        $event = $this->changeRequest($creator);

        $this->actingAs($this->roomAdmin);
        $this->put(route('events.decline', $event), ['comment' => 'Leider belegt'])->assertRedirect();

        $event->refresh();
        $this->assertFalse($event->occupancy_option);
        $this->assertSame($this->room->id, $event->room_id);
        $this->assertSame('2026-11-20 00:00:00', $event->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-20 23:59:00', $event->end_time->format('Y-m-d H:i:s'));
        $this->assertNull($event->approved_room_id);
        $this->assertNull($event->declined_room_id);

        // Anfragende Person und Ersteller*in erfahren es, die Anfrage beim Raumadmin ist erledigt
        foreach ([$this->requester, $creator] as $recipient) {
            $this->assertSame(
                [__('notification.event.room_change_request_declined', [], $recipient->fresh()->language)],
                $this->answersOf($recipient, $event)->pluck('data.title')->all()
            );
        }
        $this->assertSame(0, $this->openRequestsOf($this->roomAdmin, $event)->count());
    }

    #[Test]
    public function declining_a_first_request_still_removes_the_room(): void
    {
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        $this->actingAs($this->roomAdmin);
        $this->put(route('events.decline', $event))->assertRedirect();

        $event->refresh();
        $this->assertNull($event->room_id);
        $this->assertSame($this->room->id, $event->declined_room_id);
    }

    #[Test]
    public function a_failing_decline_of_a_change_request_keeps_the_request_open(): void
    {
        $event = $this->changeRequest($this->recipient());
        $this->partialMock(SeriesEventsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('moveShiftsWithEvent')->andThrow(new RuntimeException('Speichern gescheitert'));
        });

        $this->actingAs($this->roomAdmin);
        $this->put(route('events.decline', $event))->assertServerError();

        // Kein Zwischenstand: weder fest gebucht im angefragten Slot noch halb zurückgesprungen
        $event->refresh();
        $this->assertTrue($event->occupancy_option);
        $this->assertSame('2026-11-22 00:00:00', $event->start_time->format('Y-m-d H:i:s'));
        $this->assertSame($this->room->id, $event->approved_room_id);
    }

    #[Test]
    public function cancelling_a_booking_forgets_the_confirmed_slot(): void
    {
        // Verkürzt durch eine Person ohne Direktbuchrecht: die ursprüngliche Belegung ist gemerkt
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', [
            'approved_room_id' => $this->room->id,
            'approved_start_time' => '2026-11-20 00:00:00',
            'approved_end_time' => '2026-11-20 23:59:00',
        ]);

        $this->actingAs($this->roomAdmin);
        $this->put(route('events.decline', $event))->assertRedirect();

        $event->refresh();
        $this->assertNull($event->room_id);
        $this->assertNull($event->approved_room_id);
        $this->assertNull($event->approved_start_time);

        // Raum wieder eintragen ist damit eine neue Anfrage, keine stille Rückbuchung
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $this->putJson(route('events.update', $event), $this->updatePayload($event, ['roomId' => $this->room->id]))
            ->assertSuccessful()
            ->assertJsonPath('room_request', 'created');
        $this->assertTrue($event->fresh()->occupancy_option);
    }

    #[Test]
    public function planning_events_and_events_without_room_keep_no_confirmed_slot(): void
    {
        $attributes = [
            'approved_room_id' => $this->room->id,
            'approved_start_time' => '2026-11-20 00:00:00',
            'approved_end_time' => '2026-11-20 23:59:00',
        ];
        $planned = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', $attributes);
        $planned->update(['is_planning' => true]);
        $withoutRoom = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', $attributes);
        $withoutRoom->update(['room_id' => null]);

        $this->assertNull($planned->fresh()->approved_room_id);
        $this->assertNull($withoutRoom->fresh()->approved_room_id);
    }

    #[Test]
    public function a_partially_loaded_event_keeps_its_confirmed_slot_on_save(): void
    {
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', [
            'approved_room_id' => $this->room->id,
            'approved_start_time' => '2026-11-20 00:00:00',
            'approved_end_time' => '2026-11-20 23:59:00',
        ]);

        $partial = Event::query()->select(['id', 'name', 'start_time', 'end_time'])->findOrFail($event->id);
        $partial->update(['name' => 'Umbenannt']);

        $event->refresh();
        $this->assertSame($this->room->id, $event->approved_room_id);
    }

    #[Test]
    public function accepting_a_change_request_confirms_the_new_slot_and_answers_requester_and_creator(): void
    {
        $creator = $this->recipient();
        $event = $this->changeRequest($creator);

        $this->actingAs($this->roomAdmin);
        $this->put(route('events.accept', $event))->assertRedirect();

        $event->refresh();
        $this->assertFalse($event->occupancy_option);
        $this->assertSame('2026-11-22 00:00:00', $event->start_time->format('Y-m-d H:i:s'));
        $this->assertNull($event->approved_room_id);
        foreach ([$this->requester, $creator] as $recipient) {
            $this->assertSame(1, $this->answersOf($recipient, $event)->count());
        }
    }

    #[Test]
    public function admin_of_the_old_room_moving_an_event_is_shown_as_requester(): void
    {
        $creator = $this->recipient();
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['user_id' => $creator->id]);
        $otherRoomAdmin = $this->recipient();
        $otherRoom = Room::factory()->create(['everyone_can_book' => false]);
        $otherRoom->users()->attach($otherRoomAdmin->id, ['is_admin' => true, 'can_request' => false]);

        // Raumadmin des bisherigen Raums verschiebt in einen Raum, in dem er nicht direkt buchen darf
        $this->actingAs($this->roomAdmin);
        $this->putJson(route('events.update', $event), $this->updatePayload($event, ['roomId' => $otherRoom->id]))
            ->assertSuccessful()
            ->assertJsonPath('room_request', 'created');

        $event->refresh();
        $this->assertTrue($event->occupancy_option);
        $this->assertSame($this->roomAdmin->id, $event->requested_by_id);
        $this->assertSame(1, $this->openRequestsOf($otherRoomAdmin, $event)->count());

        $this->actingAs($otherRoomAdmin);
        $this->getJson(route('events.description', $event))
            ->assertSuccessful()
            ->assertJsonPath('requested_by.id', $this->roomAdmin->id);

        $this->put(route('events.accept', $event))->assertRedirect();
        $this->assertSame(1, $this->answersOf($this->roomAdmin, $event)->count());
        $this->assertSame(1, $this->answersOf($creator, $event)->count());
    }

    #[Test]
    public function description_reports_the_confirmed_booking_of_a_booked_event(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', [
            'approved_room_id' => $this->room->id,
            'approved_start_time' => '2026-11-20 08:00:00',
            'approved_end_time' => '2026-11-20 18:00:00',
        ]);

        $this->getJson(route('events.description', $event))
            ->assertSuccessful()
            ->assertJsonPath('approved_booking.room_id', $this->room->id)
            ->assertJsonPath('approved_booking.start', Carbon::parse('2026-11-20 08:00:00')->utc()->toIso8601String())
            ->assertJsonPath('requested_by', null);
    }

    // ------------------------------------------------------------------ Andere Wege

    #[Test]
    public function dragging_a_confirmed_event_to_another_day_turns_it_into_a_room_request(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00');

        $this->postJson(route('events.multi-cell.move'), [
            'events' => [$event->id],
            'cell' => ['day' => '2026-11-24', 'room_id' => $this->room->id],
        ])->assertSuccessful();

        $event->refresh();
        $this->assertTrue($event->occupancy_option);
        $this->assertSame($this->requester->id, $event->requested_by_id);
        $this->assertSame(1, $this->openRequestsOf($this->roomAdmin, $event)->count());
    }

    #[Test]
    public function shortening_in_the_project_event_list_keeps_the_event_booked(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00');

        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['start_time' => '10:30', 'end_time' => '11:30']),
        ])->assertSuccessful()->assertJsonPath('event.occupancy_option', false);

        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['day' => '2026-11-21']),
        ])->assertSuccessful()->assertJsonPath('event.occupancy_option', true);

        $this->assertSame(1, $this->openRequestsOf($this->roomAdmin, $event)->count());
    }

    #[Test]
    public function renaming_an_all_day_event_in_the_project_event_list_keeps_it_booked(): void
    {
        // Der Dialog speichert ganztägig bis 23:59:00, die Terminliste bis 23:59:59 (endOfDay)
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $event = $this->confirmedAllDayEvent();

        $this->patchJson(route('event.update.single.bulk', $event), [
            'data' => $this->bulkData($event, ['name' => 'Neuer Name', 'start_time' => null, 'end_time' => null]),
        ])->assertSuccessful()->assertJsonPath('event.occupancy_option', false);

        $this->assertSame(0, $this->roomRequestsOf($this->roomAdmin, $event)->count());
    }

    #[Test]
    public function copies_do_not_inherit_the_confirmed_booking_or_the_requester(): void
    {
        $event = $this->changeRequest($this->recipient());

        $copy = $event->replicate();

        $this->assertNull($copy->approved_room_id);
        $this->assertNull($copy->approved_start_time);
        $this->assertNull($copy->requested_by_id);
    }

    // ------------------------------------------------------------------ Doppeltes Absenden

    #[Test]
    public function submitting_the_same_dialog_twice_creates_the_event_only_once(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);
        $payload = $this->storePayload(['clientRequestId' => 'dialog-4711']);

        $firstId = $this->postJson(route('events.store'), $payload)->assertSuccessful()->json('data.id')
            ?? Event::query()->latest('id')->value('id');
        $secondId = $this->postJson(route('events.store'), $payload)->assertSuccessful()->json('data.id')
            ?? Event::query()->latest('id')->value('id');

        $this->assertSame($firstId, $secondId);
        $this->assertSame(1, Event::query()->where('eventName', 'Probe')->count());
        $this->assertSame(1, $this->openRequestsOf($this->roomAdmin, Event::query()->findOrFail($firstId))->count());
    }

    #[Test]
    public function a_new_dialog_creates_a_new_event(): void
    {
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->requester);

        $this->postJson(route('events.store'), $this->storePayload(['clientRequestId' => 'dialog-1']))->assertSuccessful();
        $this->postJson(route('events.store'), $this->storePayload(['clientRequestId' => 'dialog-2']))->assertSuccessful();
        $this->postJson(route('events.store'), $this->storePayload())->assertSuccessful();

        $this->assertSame(3, Event::query()->where('eventName', 'Probe')->count());
    }

    // ------------------------------------------------------------------ Mail im Hintergrund

    #[Test]
    public function the_room_request_mail_is_queued_while_the_bell_entry_is_stored_at_once(): void
    {
        NotificationSetting::query()
            ->where('user_id', $this->roomAdmin->id)
            ->where('type', NotificationEnum::NOTIFICATION_ROOM_REQUEST->value)
            ->update(['enabled_email' => true, 'frequency' => NotificationFrequencyEnum::IMMEDIATELY->value]);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        app(RoomRequestNotificationService::class)->notifyRoomAdmins($event);

        $this->assertSame(1, $this->openRequestsOf($this->roomAdmin, $event)->count());
        Bus::assertDispatched(
            SendNotificationMailJob::class,
            fn (SendNotificationMailJob $job): bool => $job->notifiable->is($this->roomAdmin)
                && $job->notification instanceof RoomRequestNotification
        );
    }

    #[Test]
    public function the_queued_mail_job_sends_only_the_mail(): void
    {
        Notification::fake();
        $notification = new RoomRequestNotification((object) [
            'type' => NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
            'title' => 'Neue Raumanfrage',
        ]);

        (new SendNotificationMailJob($this->roomAdmin, $notification))->handle();

        Notification::assertSentTo(
            $this->roomAdmin,
            RoomRequestNotification::class,
            static fn (RoomRequestNotification $sent, array $channels): bool => $channels === ['mail']
        );
    }

    #[Test]
    public function the_mail_job_is_dropped_when_the_triggering_transaction_rolls_back(): void
    {
        // Echte (synchrone) Warteschlange statt Fake: nur sie wartet auf den Commit
        config(['queue.default' => 'sync']);
        Bus::fake([self::class]);
        Queue::fake([self::class]);
        Notification::fake();
        $notification = new RoomRequestNotification((object) [
            'type' => NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
            'title' => 'Neue Raumanfrage',
        ]);

        // Wie die KI-Vorschau (dry_run): alles wird zurückgerollt
        DB::beginTransaction();
        SendNotificationMailJob::dispatch($this->roomAdmin, $notification);
        DB::rollBack();
        Notification::assertNothingSent();

        DB::transaction(fn () => SendNotificationMailJob::dispatch($this->roomAdmin, $notification));
        Notification::assertSentTo($this->roomAdmin, RoomRequestNotification::class);
    }

    #[Test]
    public function without_immediate_mail_no_mail_job_is_queued(): void
    {
        NotificationSetting::query()
            ->where('user_id', $this->roomAdmin->id)
            ->where('type', NotificationEnum::NOTIFICATION_ROOM_REQUEST->value)
            ->update(['enabled_email' => false]);
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        app(RoomRequestNotificationService::class)->notifyRoomAdmins($event);

        $this->assertSame(1, $this->openRequestsOf($this->roomAdmin, $event)->count());
        Bus::assertNotDispatched(SendNotificationMailJob::class);
    }

    // ------------------------------------------------------------------ Helfer

    // ------------------------------------------------------------------ Antwort aus der Terminliste im Projekt

    #[Test]
    public function room_admin_confirms_a_request_from_the_event_list_and_gets_the_row_back(): void
    {
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        $this->actingAs($this->roomAdmin);
        $this->putJson(route('events.accept', $event), ['adminComment' => 'Passt'])
            ->assertOk()
            ->assertJsonPath('event.id', $event->id)
            ->assertJsonPath('event.occupancy_option', false)
            ->assertJsonPath('event.room.id', $this->room->id);

        $event->refresh();
        $this->assertFalse($event->occupancy_option);
        $this->assertSame($this->room->id, $event->room_id);
        $this->assertSame(
            [__('notification.event.room_request_accept', [], $this->requester->fresh()->language)],
            $this->answersOf($this->requester, $event)->pluck('data.title')->all()
        );
    }

    #[Test]
    public function room_admin_declines_a_request_from_the_event_list_and_the_row_loses_its_room(): void
    {
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        $this->actingAs($this->roomAdmin);
        $this->putJson(route('events.decline', $event), ['comment' => ''])
            ->assertOk()
            ->assertJsonPath('event.id', $event->id)
            ->assertJsonPath('event.occupancy_option', false)
            ->assertJsonPath('event.room', null);

        $event->refresh();
        $this->assertNull($event->room_id);
        $this->assertSame($this->room->id, $event->declined_room_id);
        $this->assertSame(
            [__('notification.event.room_request_declined', [], $this->requester->fresh()->language)],
            $this->answersOf($this->requester, $event)->pluck('data.title')->all()
        );
    }

    #[Test]
    public function direct_booking_permission_answers_requests_from_the_event_list(): void
    {
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST], $this->recipient());
        $this->putJson(route('events.accept', $event))->assertOk()->assertJsonPath('event.occupancy_option', false);
    }

    #[Test]
    public function declining_a_change_request_from_the_event_list_returns_the_confirmed_booking(): void
    {
        $event = $this->changeRequest($this->recipient());

        $this->actingAs($this->roomAdmin);
        $this->putJson(route('events.decline', $event))
            ->assertOk()
            ->assertJsonPath('event.occupancy_option', false)
            ->assertJsonPath('event.room.id', $this->room->id)
            ->assertJsonPath('event.day', '2026-11-20');
    }

    #[Test]
    public function a_request_already_answered_cannot_be_answered_again_from_the_event_list(): void
    {
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        $this->actingAs($this->roomAdmin);
        $this->putJson(route('events.accept', $event))->assertOk();
        $this->putJson(route('events.accept', $event))->assertStatus(409);
    }

    #[Test]
    public function people_without_the_right_cannot_answer_from_the_event_list(): void
    {
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST], $this->recipient());
        $this->putJson(route('events.accept', $event))->assertForbidden();
        $this->putJson(route('events.decline', $event))->assertForbidden();
        $this->assertTrue($event->fresh()->occupancy_option);
    }

    #[Test]
    public function inertia_callers_are_still_redirected_after_answering(): void
    {
        $event = $this->confirmedEvent('2026-11-20 10:00:00', '2026-11-20 12:00:00', ['occupancy_option' => true]);

        $this->actingAs($this->roomAdmin);
        $this->put(route('events.accept', $event), [], ['X-Inertia' => 'true', 'Accept' => 'application/json'])
            ->assertRedirect();
    }

    private function recipient(): User
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);

        return $user;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function confirmedEvent(string $start, string $end, array $attributes = []): Event
    {
        return Event::factory()->create(array_replace([
            'user_id' => $this->requester->id,
            'room_id' => $this->room->id,
            'occupancy_option' => false,
            'is_planning' => false,
            'is_series' => false,
            'start_time' => $start,
            'end_time' => $end,
            'allDay' => false,
        ], $attributes));
    }

    private function confirmedAllDayEvent(): Event
    {
        return $this->confirmedEvent('2026-11-20 00:00:00', '2026-11-20 23:59:00', ['allDay' => true]);
    }

    /**
     * Offene Änderungsanfrage (Stand nach EventChangeApprovalService): bestätigt war der 20.11. ganztägig, die
     * anfragende Person hat auf den 22.11. verschoben. Angelegt hat den Termin $creator.
     */
    private function changeRequest(User $creator): Event
    {
        return $this->confirmedEvent('2026-11-22 00:00:00', '2026-11-22 23:59:00', [
            'user_id' => $creator->id,
            'allDay' => true,
            'occupancy_option' => true,
            'approved_room_id' => $this->room->id,
            'approved_start_time' => '2026-11-20 00:00:00',
            'approved_end_time' => '2026-11-20 23:59:00',
            'requested_by_id' => $this->requester->id,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Event $event, array $overrides = []): array
    {
        return array_replace([
            'start' => $event->start_time->format('Y-m-d H:i'),
            'end' => $event->end_time->format('Y-m-d H:i'),
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $event->event_type_id,
            'roomId' => $event->room_id,
            'title' => $event->name,
            'eventName' => $event->eventName,
            'isOption' => true,
            'audience' => false,
            'isLoud' => false,
            'allDay' => (bool) $event->allDay,
            'isPlanning' => false,
            'noNotifications' => true,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function storePayload(array $overrides = []): array
    {
        return array_replace([
            'start' => '2026-11-27 10:00',
            'end' => '2026-11-27 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => EventType::factory()->create()->id,
            'roomId' => $this->room->id,
            'title' => 'Probe',
            'eventName' => 'Probe',
            'isOption' => true,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
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
            'day' => '2026-11-20',
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
        return $this->notificationsOf($user, $event, NotificationEnum::NOTIFICATION_ROOM_REQUEST);
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function openRequestsOf(User $user, Event $event): Collection
    {
        return $this->roomRequestsOf($user, $event)
            ->filter(static fn (DatabaseNotification $notification): bool => !isset($notification->data['handledStatus']))
            ->values();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function answersOf(User $user, Event $event): Collection
    {
        return $this->notificationsOf($user, $event, NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST);
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function notificationsOf(User $user, Event $event, NotificationEnum $type): Collection
    {
        return $user->notifications()->get()
            ->filter(static fn (DatabaseNotification $notification): bool =>
                ($notification->data['type'] ?? null) === $type->value
                && (int) ($notification->data['eventId'] ?? 0) === (int) $event->id)
            ->values();
    }
}
