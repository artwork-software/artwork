<?php

namespace Tests\Feature\Modules\Event;

use App\Settings\EventSettings;
use Artwork\Core\Http\Middleware\HandleInertiaRequests;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Services\DirectBookingActivationService;
use Artwork\Modules\Event\Services\EventVerificationService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Services\PermissionCatalogPresenter;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Instanz-Einstellung "Termine immer direkt buchbar" (18.09.2026): keine Raumbelegungsanfragen, keine
 * Terminverifizierung – jede Person mit Anlage-Recht bucht im Kalender und Planungskalender direkt.
 */
final class DirectBookingSettingTest extends FeatureTestCase
{
    private function setDirectBooking(bool $enabled): void
    {
        $settings = app(EventSettings::class);
        $settings->always_direct_booking = $enabled;
        $settings->save();
    }

    private function storePayload(EventType $eventType, Room $room, array $overrides = []): array
    {
        return array_replace([
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Direktbuchung',
            'eventName' => 'Probe',
            'isOption' => true,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
        ], $overrides);
    }

    #[Test]
    public function request_only_user_books_a_restricted_room_directly_when_setting_is_active(): void
    {
        $this->setDirectBooking(true);
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $eventType = EventType::factory()->create();
        $this->mock(RoomRequestNotificationService::class)->shouldNotReceive('notifyRoomAdmins');

        // Client sendet isOption=true (Anfrage) – wird verworfen
        $this->postJson(route('events.store'), $this->storePayload($eventType, $room))->assertSuccessful();

        $event = Event::query()->where('room_id', $room->id)->firstOrFail();
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function request_only_user_still_creates_a_request_when_setting_is_off(): void
    {
        $this->setDirectBooking(false);
        $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $eventType = EventType::factory()->create();

        $this->postJson(route('events.store'), $this->storePayload($eventType, $room, ['isOption' => false]))
            ->assertSuccessful();

        $this->assertTrue((bool) Event::query()->where('room_id', $room->id)->firstOrFail()->occupancy_option);
    }

    #[Test]
    public function planned_event_is_booked_directly_without_fixed_planning_permission(): void
    {
        $this->setDirectBooking(true);
        $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $eventType = EventType::factory()->create();

        $this->postJson(route('events.store'), $this->storePayload($eventType, $room, ['isPlanning' => true]))
            ->assertSuccessful();

        $event = Event::query()->where('room_id', $room->id)->firstOrFail();
        $this->assertTrue((bool) $event->is_planning);
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function moving_an_event_into_a_restricted_room_creates_no_request_when_setting_is_active(): void
    {
        $this->setDirectBooking(true);
        $user = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $openRoom = Room::factory()->create(['everyone_can_book' => true]);
        $restrictedRoom = Room::factory()->create(['everyone_can_book' => false]);
        $eventType = EventType::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'room_id' => $openRoom->id,
            'event_type_id' => $eventType->id,
            'occupancy_option' => false,
        ]);
        $this->mock(RoomRequestNotificationService::class)->shouldNotReceive('notifyRoomAdmins');

        $this->putJson(route('events.update', $event), $this->storePayload($eventType, $restrictedRoom, [
            'isOption' => false,
        ]))->assertSuccessful();

        $event->refresh();
        $this->assertSame($restrictedRoom->id, $event->room_id);
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function bulk_creation_is_open_to_request_permission_when_setting_is_active(): void
    {
        $this->setDirectBooking(true);
        $user = $this->actingAsUserWith([PermissionEnum::EVENT_REQUEST]);
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $eventType = EventType::factory()->create();

        $payload = ['events' => [[
            'name' => 'Bulk',
            'day' => '2026-11-12',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'room' => ['id' => $room->id],
            'type' => ['id' => $eventType->id],
        ]]];

        $this->postJson(route('events.bulk.store', $project), $payload)->assertSuccessful();

        $this->setDirectBooking(false);
        $this->postJson(route('events.bulk.store', $project), $payload)->assertForbidden();
    }

    #[Test]
    public function verification_request_confirms_the_planned_event_immediately(): void
    {
        $this->setDirectBooking(true);
        $verifier = User::factory()->create();
        $eventType = EventType::factory()->create([
            'verification_mode' => 'specific',
            'specific_verifier_id' => $verifier->id,
        ]);
        $creator = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $creator->id,
            'event_type_id' => $eventType->id,
            'is_planning' => true,
        ]);

        app(EventVerificationService::class)->requestVerification($event, $creator);

        $this->assertFalse((bool) $event->fresh()->is_planning);
        $this->assertSame(0, EventVerification::where('event_id', $event->id)->count());
    }

    #[Test]
    public function activation_accepts_open_room_requests_and_pending_verifications_once(): void
    {
        $this->setDirectBooking(false);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $requests = Event::factory()->count(2)->create(['room_id' => $room->id, 'occupancy_option' => true]);
        $plannedWithVerification = Event::factory()->create(['is_planning' => true]);
        $verifier = User::factory()->create();
        EventVerification::create([
            'uuid' => Str::uuid()->toString(),
            'event_id' => $plannedWithVerification->id,
            'verifier_id' => $verifier->id,
            'verifier_type' => User::class,
            'status' => 'pending',
            'request_user_id' => $verifier->id,
        ]);
        $untouchedPlanned = Event::factory()->create(['is_planning' => true]);

        $service = app(DirectBookingActivationService::class);
        $this->assertSame(2, $service->openRoomRequestsCount());
        $this->assertSame(1, $service->pendingVerificationsCount());

        $result = $service->apply(true);

        $this->assertSame(['accepted_room_requests' => 2, 'approved_verifications' => 1], $result);
        $this->assertTrue((bool) app(EventSettings::class)->refresh()->always_direct_booking);
        foreach ($requests as $request) {
            $this->assertFalse((bool) $request->fresh()->occupancy_option);
        }
        $this->assertFalse((bool) $plannedWithVerification->fresh()->is_planning);
        $this->assertSame('approved', EventVerification::where('event_id', $plannedWithVerification->id)->value('status'));
        $this->assertTrue((bool) $untouchedPlanned->fresh()->is_planning, 'Geplante Termine ohne Anfrage bleiben geplant.');

        // Erneutes Speichern mit aktivem Schalter übernimmt nichts mehr
        $this->assertSame(['accepted_room_requests' => 0, 'approved_verifications' => 0], $service->apply(true));
    }

    #[Test]
    public function settings_endpoint_switches_the_setting_and_clears_the_menu_cache(): void
    {
        $admin = $this->actingAsAdmin();
        Cache::put("user:{$admin->id}:can_see_incoming_requests", true, 300);

        $this->patch(route('event.standard.values.update'), ['always_direct_booking' => true])->assertSuccessful();

        $this->assertTrue((bool) app(EventSettings::class)->refresh()->always_direct_booking);
        $this->assertNull(Cache::get("user:{$admin->id}:can_see_incoming_requests"));

        $this->patch(route('event.standard.values.update'), ['always_direct_booking' => false])->assertSuccessful();
        $this->assertFalse((bool) app(EventSettings::class)->refresh()->always_direct_booking);
    }

    #[Test]
    public function shared_props_hide_the_verification_pages_when_setting_is_active(): void
    {
        $this->setDirectBooking(true);
        $this->actingAsAdmin();

        $shared = app(HandleInertiaRequests::class)->share(Request::create('/dashboard', 'GET'));

        $this->assertTrue($shared['event_direct_booking_only']);
        $this->assertFalse($shared['canSeeIncomingRequests']);
        $this->assertFalse($shared['canSeeEventVerifications']);
    }

    #[Test]
    public function verification_pages_redirect_to_the_calendar_when_setting_is_active(): void
    {
        $this->setDirectBooking(true);
        $this->actingAsAdmin();

        $this->get(route('event-verifications.index'))->assertRedirect(route('events'));
        $this->get(route('event-verifications.sent'))->assertRedirect(route('events'));
    }

    #[Test]
    public function permission_catalog_marks_the_affected_permissions_as_superseded(): void
    {
        $this->setDirectBooking(true);
        $catalog = app(PermissionCatalog::class);

        foreach (
            [
            PermissionEnum::EVENT_REQUEST,
            PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST,
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
            PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR,
            ] as $permission
        ) {
            $definition = $catalog->definition($permission);
            $this->assertNotNull($definition?->supersededBy, $permission->value);
            $this->assertSame('event_direct_booking_only', $definition->supersededBy->value);
        }
        $this->assertNull($catalog->definition(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR)?->supersededBy);

        $state = app(PermissionCatalogPresenter::class)->instanceState();
        $this->assertTrue($state['settings']['event_direct_booking_only']);
    }
}
