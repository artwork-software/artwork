<?php

namespace Tests\Feature\Modules\Calendar;

use Artwork\Modules\Calendar\Services\EventPlanningCalendarService;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Durchlauf Planungskalender 14.10.2026: Seite und Termin-Daten wie im Kalender.
 */
final class PlanningCalendarRobustnessTest extends FeatureTestCase
{
    #[Test]
    public function planning_calendar_opens_without_calendar_settings(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->calendar_settings()->delete();
        $admin->unsetRelation('calendar_settings');

        $this->get(route('planning-event-calendar.index'))->assertOk();

        $this->assertNotNull($admin->calendar_settings()->first());
    }

    #[Test]
    public function an_end_date_at_midnight_includes_the_whole_last_day(): void
    {
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => true,
            'start_time' => '2026-10-20 18:00:00',
            'end_time' => '2026-10-20 20:00:00',
        ]);

        $events = $this->planningEventsOf($room, Carbon::parse('2026-10-14'), Carbon::parse('2026-10-20'));

        $this->assertSame([$event->id], $events->pluck('id')->all());
    }

    #[Test]
    public function planned_events_carry_properties_series_and_timelines_like_in_the_calendar(): void
    {
        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => true,
            'start_time' => '2026-10-15 10:00:00',
            'end_time' => '2026-10-15 12:00:00',
        ]);
        $property = EventProperty::create(['name' => 'Premiere', 'icon' => 'IconStar']);
        $event->eventProperties()->attach($property->id);
        Timeline::create([
            'event_id' => $event->id,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-15',
            'start' => '10:00',
            'end' => '11:00',
            'description' => 'Aufbau',
        ]);

        $dto = $this->planningEventsOf($room, Carbon::parse('2026-10-14'), Carbon::parse('2026-10-20'))->first();

        $this->assertSame([$property->id], array_column($dto->eventProperties, 'id'));
        $this->assertTrue($dto->hasTimelines);
        $this->assertNull($dto->series_id);
    }

    /**
     * Geplante Termine liegen hinter dem Planungskalender-Recht – auch im Dienstplan (vorher sah sie jede Person
     * mit Dienstplan-Sichtrecht).
     */
    #[Test]
    public function the_duty_roster_shows_planned_events_only_with_the_planning_permission(): void
    {
        $room = Room::factory()->create();
        $planned = Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => true,
            'start_time' => '2026-10-15 10:00:00',
            'end_time' => '2026-10-15 12:00:00',
        ]);
        $fixed = Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => false,
            'start_time' => '2026-10-15 14:00:00',
            'end_time' => '2026-10-15 16:00:00',
        ]);
        $rosterEventIds = function () use ($room): array {
            $rooms = Room::query()->whereKey($room->id)->get();
            app(ShiftCalendarService::class)->filterRoomsEventsAndShifts(
                $rooms,
                new UserFilter(),
                Carbon::parse('2026-10-14'),
                Carbon::parse('2026-10-20')->endOfDay(),
            );

            return collect($rooms->first()->events)->pluck('id')->sort()->values()->all();
        };

        $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN]);
        $this->assertSame([$fixed->id], $rosterEventIds());

        $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN, PermissionEnum::CAN_SEE_PLANNING_CALENDAR]);
        $this->assertSame([$planned->id, $fixed->id], $rosterEventIds());
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Artwork\Modules\Calendar\DTO\EventDTOWithVerifications>
     */
    private function planningEventsOf(Room $room, Carbon $startDate, Carbon $endDate): \Illuminate\Support\Collection
    {
        $admin = $this->actingAsAdmin();
        $settings = $admin->calendar_settings()->firstOrCreate();

        $rooms = app(EventPlanningCalendarService::class)->filterRoomsEvents(
            collect([$room]),
            new UserFilter(),
            $startDate,
            $endDate,
            $settings,
        );

        return collect($rooms->first()->events);
    }
}
