<?php

namespace Tests\Feature\Authorization;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function plan_events_directly_alone_turns_a_planned_event_into_a_request(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST,
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
        ]);

        $event = $this->storeEvent(isPlanning: true);

        $this->assertTrue($event->is_planning);
        $this->assertTrue((bool) $event->occupancy_option, 'Ohne Planungs-Recht darf nur angefragt werden.');
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

    private function storeEvent(bool $isPlanning): Event
    {
        $room = Room::factory()->create(['everyone_can_book' => false]);
        $eventType = EventType::factory()->create();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Planungstest',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => $isPlanning,
        ]);
        $response->assertSuccessful();

        return Event::query()->where('room_id', $room->id)->firstOrFail();
    }
}
