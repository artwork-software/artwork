<?php

namespace Tests\Feature\Modules\Calendar;

use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * „Leere Räume ausblenden“: ein Raum erscheint genau dann, wenn er mit den aktuellen Filtern und
 * Anzeigeeinstellungen etwas zeigt – nicht schon, weil irgendein Termin oder eine Schicht darin liegt.
 */
final class HideUnoccupiedRoomsVisibleContentTest extends FeatureTestCase
{
    private const START = '2031-03-09';
    private const END = '2031-03-11';

    #[Test]
    public function the_planning_calendar_hides_rooms_with_only_fixed_events_while_fixed_events_are_hidden(): void
    {
        $admin = $this->actingAsAdmin();
        $roomWithFixedEvent = $this->roomWithEvent(isPlanning: false);
        $roomWithPlannedEvent = $this->roomWithEvent(isPlanning: true);
        $admin->calendarSettingsFor(true, false)
            ->update(['hide_unoccupied_rooms' => true, 'show_unplanned_events' => false]);

        $roomIds = $this->roomIdsInCalendarPayload(isPlanning: true);

        $this->assertContains($roomWithPlannedEvent->id, $roomIds);
        $this->assertNotContains($roomWithFixedEvent->id, $roomIds);
    }

    #[Test]
    public function the_planning_calendar_shows_rooms_with_only_fixed_events_while_fixed_events_are_shown(): void
    {
        $admin = $this->actingAsAdmin();
        $roomWithFixedEvent = $this->roomWithEvent(isPlanning: false);
        $admin->calendarSettingsFor(true, false)
            ->update(['hide_unoccupied_rooms' => true, 'show_unplanned_events' => true]);

        $this->assertContains($roomWithFixedEvent->id, $this->roomIdsInCalendarPayload(isPlanning: true));
    }

    #[Test]
    public function the_calendar_keeps_rooms_whose_only_content_are_visible_shift_cards(): void
    {
        $admin = $this->actingAsAdmin();
        $roomWithShift = $this->roomWithStandaloneShift();
        $admin->calendarSettingsFor(false, false)->update(['hide_unoccupied_rooms' => true, 'work_shifts' => true]);

        $this->assertContains($roomWithShift->id, $this->roomIdsInCalendarPayload(isPlanning: false));
    }

    #[Test]
    public function the_calendar_hides_rooms_with_only_shifts_while_shift_cards_are_hidden(): void
    {
        $admin = $this->actingAsAdmin();
        $roomWithShift = $this->roomWithStandaloneShift();
        $admin->calendarSettingsFor(false, false)->update(['hide_unoccupied_rooms' => true, 'work_shifts' => false]);

        $this->assertNotContains($roomWithShift->id, $this->roomIdsInCalendarPayload(isPlanning: false));
    }

    #[Test]
    public function the_calendar_hides_rooms_with_only_shifts_without_shift_plan_right(): void
    {
        $user = $this->actingAsUserWith([]);
        $roomWithShift = $this->roomWithStandaloneShift();
        $user->calendarSettingsFor(false, false)->update(['hide_unoccupied_rooms' => true, 'work_shifts' => true]);

        $this->assertNotContains($roomWithShift->id, $this->roomIdsInCalendarPayload(isPlanning: false));
    }

    #[Test]
    public function the_shift_plan_keeps_rooms_with_planned_events_it_shows_to_planning_users(): void
    {
        $user = $this->actingAsUserWith([
            PermissionEnum::VIEW_SHIFT_PLAN->value,
            PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value,
        ]);
        $roomWithPlannedEvent = $this->roomWithEvent(isPlanning: true);
        // „Geplante Termine anzeigen“ gibt es im Dienstplan nicht – ein Altwert darf nicht mitentscheiden
        $settings = $user->shift_plan_settings()->updateOrCreate(
            [],
            ['hide_unoccupied_rooms' => true, 'show_planned_events' => false]
        );

        $this->assertContains($roomWithPlannedEvent->id, $this->shiftPlanRoomIds($settings));
    }

    #[Test]
    public function the_shift_plan_hides_rooms_with_only_planned_events_without_planning_right(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $roomWithPlannedEvent = $this->roomWithEvent(isPlanning: true);
        $settings = $user->shift_plan_settings()->updateOrCreate(
            [],
            ['hide_unoccupied_rooms' => true, 'show_planned_events' => true]
        );

        $this->assertNotContains($roomWithPlannedEvent->id, $this->shiftPlanRoomIds($settings));
    }

    #[Test]
    public function the_shift_plan_applies_the_project_state_filter_to_its_events(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create(['relevant_for_disposition' => true]);
        $matchingState = ProjectState::factory()->create();
        $otherState = ProjectState::factory()->create();
        $matchingEvent = $this->eventInRoom($room, Project::factory()->create(['state' => $matchingState->id]));
        $otherEvent = $this->eventInRoom($room, Project::factory()->create(['state' => $otherState->id]));
        $filter = new UserFilter(['project_state_ids' => [$matchingState->id]]);

        $result = app(ShiftCalendarService::class)->filterRoomsEventsAndShifts(
            Room::query()->whereKey($room->id)->get(),
            $filter,
            Carbon::parse(self::START)->startOfDay(),
            Carbon::parse(self::END)->endOfDay()
        );

        $eventIds = collect($result['rooms']->first()->events)->pluck('id');
        $this->assertContains($matchingEvent->id, $eventIds);
        $this->assertNotContains($otherEvent->id, $eventIds);
    }

    private function roomWithEvent(bool $isPlanning): Room
    {
        $room = Room::factory()->create(['relevant_for_disposition' => true]);
        Event::factory()->create([
            'room_id' => $room->id,
            'is_planning' => $isPlanning,
            'start_time' => '2031-03-10 10:00:00',
            'end_time' => '2031-03-10 12:00:00',
        ]);

        return $room;
    }

    private function eventInRoom(Room $room, Project $project): Event
    {
        return Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => $project->id,
            'is_planning' => false,
            'start_time' => '2031-03-10 10:00:00',
            'end_time' => '2031-03-10 12:00:00',
        ]);
    }

    private function roomWithStandaloneShift(): Room
    {
        $room = Room::factory()->create(['relevant_for_disposition' => true]);
        Shift::query()->create([
            'event_id' => null,
            'room_id' => $room->id,
            'craft_id' => Craft::factory()->create()->id,
            'start_date' => '2031-03-10',
            'end_date' => '2031-03-10',
            'start' => '08:00',
            'end' => '16:00',
        ]);

        return $room;
    }

    /**
     * @return list<int>
     */
    private function roomIdsInCalendarPayload(bool $isPlanning): array
    {
        $response = $this->getJson(route('events.all', [
            'start_date' => self::START,
            'end_date' => self::END,
            'isPlanning' => $isPlanning ? 'true' : 'false',
        ]))->assertOk();

        return collect($response->json('calendar'))->pluck('roomId')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @return list<int>
     */
    private function shiftPlanRoomIds(object $settings): array
    {
        return app(CalendarDataService::class)->getFilteredRooms(
            null,
            $settings,
            Carbon::parse(self::START)->startOfDay(),
            Carbon::parse(self::END)->endOfDay(),
            true,
            isShiftPlan: true
        )->pluck('id')->all();
    }
}
