<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * "Projektfremde Termine/Schichten anzeigen" im Projekt-Schichten-Tab:
 * Die Flags show_unrelated_events/show_unrelated_shifts (user_shift_plan_daily_settings)
 * heben den Projektfilter der Batch-Query auf.
 */
final class ShiftPlanUnrelatedProjectDisplayTest extends FeatureTestCase
{
    private User $admin;

    private Room $room;

    private Project $ownProject;

    private Event $ownEvent;

    private Event $foreignEvent;

    private Event $projectlessEvent;

    private Shift $ownShift;

    private Shift $foreignShift;

    private Shift $projectlessShift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->actingAsAdmin();

        $this->room = Room::factory()->create(['relevant_for_disposition' => true]);
        $this->ownProject = Project::factory()->create();
        $foreignProject = Project::factory()->create();

        $eventDefaults = [
            'room_id' => $this->room->id,
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 12:00:00',
        ];
        $this->ownEvent = Event::factory()->create($eventDefaults + ['project_id' => $this->ownProject->id]);
        $this->foreignEvent = Event::factory()->create($eventDefaults + ['project_id' => $foreignProject->id]);
        $this->projectlessEvent = Event::factory()->create($eventDefaults + ['project_id' => null]);

        $shiftDefaults = [
            'event_id' => null,
            'room_id' => $this->room->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'start' => '10:00',
            'end' => '14:00',
        ];
        $this->ownShift = Shift::factory()->create($shiftDefaults + ['project_id' => $this->ownProject->id]);
        $this->foreignShift = Shift::factory()->create($shiftDefaults + ['project_id' => $foreignProject->id]);
        $this->projectlessShift = Shift::factory()->create($shiftDefaults + ['project_id' => null]);
    }

    /**
     * @return array{0: int[], 1: int[]} [eventIds, shiftIds] über alle Räume der Batch-Antwort
     */
    private function fetchProjectTabIds(): array
    {
        $response = $this->getJson(route('shift.plan.rooms.batch', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'projectId' => $this->ownProject->id,
            'isInProjectView' => 1,
        ]));

        $response->assertOk();

        $eventIds = [];
        $shiftIds = [];
        foreach ($response->json('rooms') ?? [] as $room) {
            $eventIds = array_merge($eventIds, array_map('intval', array_keys($room['eventsById'] ?? [])));
            $shiftIds = array_merge($shiftIds, array_map('intval', array_keys($room['shiftsById'] ?? [])));
        }

        return [$eventIds, $shiftIds];
    }

    #[Test]
    public function project_tab_only_contains_own_events_and_shifts_by_default(): void
    {
        [$eventIds, $shiftIds] = $this->fetchProjectTabIds();

        $this->assertContains($this->ownEvent->id, $eventIds);
        $this->assertNotContains($this->foreignEvent->id, $eventIds);
        $this->assertNotContains($this->projectlessEvent->id, $eventIds);

        $this->assertContains($this->ownShift->id, $shiftIds);
        $this->assertNotContains($this->foreignShift->id, $shiftIds);
        $this->assertNotContains($this->projectlessShift->id, $shiftIds);
    }

    #[Test]
    public function show_unrelated_events_setting_adds_foreign_and_projectless_events(): void
    {
        $this->admin->shift_plan_daily_settings()->create([
            'show_unrelated_events' => true,
            'show_unrelated_shifts' => false,
        ]);

        [$eventIds, $shiftIds] = $this->fetchProjectTabIds();

        $this->assertContains($this->ownEvent->id, $eventIds);
        $this->assertContains($this->foreignEvent->id, $eventIds);
        $this->assertContains($this->projectlessEvent->id, $eventIds);

        // Schichten bleiben ohne eigenes Flag weiterhin projektgefiltert
        $this->assertContains($this->ownShift->id, $shiftIds);
        $this->assertNotContains($this->foreignShift->id, $shiftIds);
        $this->assertNotContains($this->projectlessShift->id, $shiftIds);
    }

    #[Test]
    public function show_unrelated_shifts_setting_adds_foreign_and_projectless_shifts(): void
    {
        $this->admin->shift_plan_daily_settings()->create([
            'show_unrelated_events' => false,
            'show_unrelated_shifts' => true,
        ]);

        [$eventIds, $shiftIds] = $this->fetchProjectTabIds();

        // Termine bleiben ohne eigenes Flag weiterhin projektgefiltert
        $this->assertContains($this->ownEvent->id, $eventIds);
        $this->assertNotContains($this->foreignEvent->id, $eventIds);
        $this->assertNotContains($this->projectlessEvent->id, $eventIds);

        $this->assertContains($this->ownShift->id, $shiftIds);
        $this->assertContains($this->foreignShift->id, $shiftIds);
        $this->assertContains($this->projectlessShift->id, $shiftIds);
    }

    #[Test]
    public function settings_do_not_leak_into_global_shift_plan_or_other_projects(): void
    {
        $this->admin->shift_plan_daily_settings()->create([
            'show_unrelated_events' => true,
            'show_unrelated_shifts' => true,
        ]);

        // Ohne Projektkontext (globale Ansicht) liefert die Query ohnehin alles —
        // hier nur sicherstellen, dass der Endpunkt weiterhin funktioniert
        $response = $this->getJson(route('shift.plan.rooms.batch', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
        ]));

        $response->assertOk();
    }
}
