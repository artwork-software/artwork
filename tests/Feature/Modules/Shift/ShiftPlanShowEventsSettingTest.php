<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * „Termine anzeigen" (user_shift_plan_settings.show_events) in der Dienstplan-Wochenansicht:
 * ist die Einstellung aus, liefert die Batch-Query keine Termine mehr (Schichten unverändert),
 * und „Räume ohne Belegung ausblenden" zählt nur noch Schichten als Belegung.
 */
final class ShiftPlanShowEventsSettingTest extends FeatureTestCase
{
    private User $admin;

    private Room $roomWithBoth;

    private Room $roomWithEventOnly;

    private Event $event;

    private Event $eventOnlyRoomEvent;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->actingAsAdmin();
        $this->admin->forceFill(['shift_plan_daily_view' => false])->save();

        $this->roomWithBoth = Room::factory()->create(['relevant_for_disposition' => true]);
        $this->roomWithEventOnly = Room::factory()->create(['relevant_for_disposition' => true]);

        $eventDefaults = [
            'project_id' => null,
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 12:00:00',
        ];
        $this->event = Event::factory()->create($eventDefaults + ['room_id' => $this->roomWithBoth->id]);
        $this->eventOnlyRoomEvent = Event::factory()->create(
            $eventDefaults + ['room_id' => $this->roomWithEventOnly->id]
        );

        $this->shift = Shift::factory()->create([
            'event_id' => null,
            'project_id' => null,
            'room_id' => $this->roomWithBoth->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'start' => '10:00',
            'end' => '14:00',
        ]);
    }

    /**
     * @return array{0: int[], 1: int[], 2: int[]} [roomIds, eventIds, shiftIds] der Batch-Antwort
     */
    private function fetchWeekViewIds(): array
    {
        $response = $this->getJson(route('shift.plan.rooms.batch', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
        ]));

        $response->assertOk();

        $roomIds = [];
        $eventIds = [];
        $shiftIds = [];
        foreach ($response->json('rooms') ?? [] as $room) {
            $roomIds[] = (int) $room['roomId'];
            $eventIds = array_merge($eventIds, array_map('intval', array_keys($room['eventsById'] ?? [])));
            $shiftIds = array_merge($shiftIds, array_map('intval', array_keys($room['shiftsById'] ?? [])));
        }

        return [$roomIds, $eventIds, $shiftIds];
    }

    #[Test]
    public function events_are_delivered_by_default(): void
    {
        $this->admin->shift_plan_settings()->create();

        [$roomIds, $eventIds, $shiftIds] = $this->fetchWeekViewIds();

        $this->assertContains($this->roomWithBoth->id, $roomIds);
        $this->assertContains($this->roomWithEventOnly->id, $roomIds);
        $this->assertContains($this->event->id, $eventIds);
        $this->assertContains($this->eventOnlyRoomEvent->id, $eventIds);
        $this->assertContains($this->shift->id, $shiftIds);
    }

    #[Test]
    public function hidden_events_are_not_delivered_but_shifts_are(): void
    {
        $this->admin->shift_plan_settings()->create(['show_events' => false]);

        [$roomIds, $eventIds, $shiftIds] = $this->fetchWeekViewIds();

        $this->assertSame([], $eventIds);
        $this->assertContains($this->shift->id, $shiftIds);
        // Ohne „Räume ohne Belegung ausblenden" bleiben alle Räume im Raster
        $this->assertContains($this->roomWithBoth->id, $roomIds);
        $this->assertContains($this->roomWithEventOnly->id, $roomIds);
    }

    #[Test]
    public function hide_unoccupied_rooms_counts_only_shifts_when_events_are_hidden(): void
    {
        $this->admin->shift_plan_settings()->create([
            'show_events' => false,
            'hide_unoccupied_rooms' => true,
        ]);

        [$roomIds, $eventIds, $shiftIds] = $this->fetchWeekViewIds();

        $this->assertContains($this->roomWithBoth->id, $roomIds);
        $this->assertNotContains($this->roomWithEventOnly->id, $roomIds);
        $this->assertSame([], $eventIds);
        $this->assertContains($this->shift->id, $shiftIds);
    }

    #[Test]
    public function hide_unoccupied_rooms_still_counts_events_when_they_are_shown(): void
    {
        $this->admin->shift_plan_settings()->create([
            'show_events' => true,
            'hide_unoccupied_rooms' => true,
        ]);

        [$roomIds, $eventIds] = $this->fetchWeekViewIds();

        $this->assertContains($this->roomWithBoth->id, $roomIds);
        $this->assertContains($this->roomWithEventOnly->id, $roomIds);
        $this->assertContains($this->eventOnlyRoomEvent->id, $eventIds);
    }
}
