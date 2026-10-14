<?php

namespace Tests\Feature\Authorization;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\Feature\FeatureTestCase;

/**
 * 09.09.2026: Kalender und Planungskalender werden getrennt berechtigt. "Termine fest planen" impliziert
 * "Im Planungskalender fest planen" nicht mehr – weder im Katalog (Rechteseite) noch im Backend-Gate.
 */
final class PlanningCalendarFixedPlanningTest extends FeatureTestCase
{
    #[Test]
    public function plan_events_directly_does_not_imply_fixed_planning_in_the_planning_calendar(): void
    {
        $expanded = app(PermissionCatalog::class)->expandWithImplied([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value]);

        $this->assertContains(PermissionEnum::EVENT_REQUEST->value, $expanded);
        $this->assertNotContains(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value, $expanded);
        $this->assertSame(
            [],
            app(PermissionCatalog::class)->impliedBy(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value),
            'Kein Recht darf das Planungs-Festplanen als Stufe enthalten – sonst warnt der Editor beim Entfernen.'
        );
    }

    /**
     * 13.10.2026: Geplante Termine kennen keine Raumanfrage mehr – ohne "Im Planungskalender fest planen"
     * gibt es statt einer Anfrage 403.
     */
    #[Test]
    public function plan_events_directly_alone_cannot_create_a_planned_event(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST,
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
        ]);

        $this->storeEventResponse(isPlanning: true)->assertForbidden();

        $this->assertSame(0, Event::query()->count());
    }

    #[Test]
    public function planning_calendar_viewer_with_request_permission_cannot_request_a_planned_event(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::EVENT_REQUEST,
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
        ]);

        $this->storeEventResponse(isPlanning: true, isOption: true)->assertForbidden();

        $this->assertSame(0, Event::query()->count());
    }

    #[Test]
    public function a_planned_event_sent_as_request_is_stored_firmly_planned(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
            PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR,
        ]);

        $event = $this->storeEvent(isPlanning: true, isOption: true);

        $this->assertTrue($event->is_planning);
        $this->assertFalse((bool) $event->occupancy_option, 'Ein vom Client gesendetes isOption wird verworfen.');
    }

    #[Test]
    public function room_admin_creates_a_planned_event_in_the_own_room_without_the_planning_permission(): void
    {
        $roomAdmin = $this->actingAsUserWith([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $room->users()->attach($roomAdmin->id, ['is_admin' => true, 'can_request' => false]);

        $event = $this->storeEvent(isPlanning: true, room: $room);

        $this->assertTrue($event->is_planning);
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function fixed_planning_permission_books_a_planned_event_directly(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
            PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR,
        ]);

        $event = $this->storeEvent(isPlanning: true);

        $this->assertTrue($event->is_planning);
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function plan_events_directly_still_books_regular_events_directly(): void
    {
        $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST]);

        $event = $this->storeEvent(isPlanning: false);

        $this->assertFalse($event->is_planning);
        $this->assertFalse((bool) $event->occupancy_option);
    }

    #[Test]
    public function fixed_planning_permission_does_not_book_regular_events_directly(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::EVENT_REQUEST,
            PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR,
        ]);

        $event = $this->storeEvent(isPlanning: false);

        $this->assertTrue((bool) $event->occupancy_option);
    }

    #[Test]
    public function backfill_migration_grants_the_planning_permission_to_holders_of_plan_events_directly(): void
    {
        $holder = $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST]);
        Permission::findOrCreate(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value, 'web');
        $other = User::factory()->create();

        $migration = require base_path(
            'database/migrations/2026_09_09_100000_backfill_plan_fixed_in_planning_calendar_permission.php'
        );
        $migration->up();
        $migration->up(); // idempotent

        $this->assertTrue($holder->fresh()->hasPermissionTo(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value));
        $this->assertFalse($other->fresh()->hasPermissionTo(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value));
        $this->assertSame(
            1,
            $holder->fresh()->permissions()->where('name', PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value)->count()
        );
    }

    private function storeEvent(bool $isPlanning, bool $isOption = false, ?Room $room = null): Event
    {
        $room ??= Room::factory()->create(['everyone_can_book' => false]);
        $this->storeEventResponse($isPlanning, $isOption, $room)->assertSuccessful();

        return Event::query()->where('room_id', $room->id)->firstOrFail();
    }

    private function storeEventResponse(bool $isPlanning, bool $isOption = false, ?Room $room = null): TestResponse
    {
        $room ??= Room::factory()->create(['everyone_can_book' => false]);
        $eventType = EventType::factory()->create();

        return $this->postJson(route('events.store'), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Planungstest',
            'isOption' => $isOption,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => $isPlanning,
        ]);
    }
}
