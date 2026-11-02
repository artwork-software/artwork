<?php

namespace Tests\Feature\Modules\Event;

use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Termin anlegen und dabei ein neues Projekt erzeugen: das Projekt bekommt das Basis-Budget und
 * der Termin hängt daran.
 */
final class StoreEventWithNewProjectTest extends FeatureTestCase
{
    #[Test]
    public function creating_an_event_with_a_new_project_sets_up_its_budget(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create(['everyone_can_book' => true]);

        $this->postJson(route('events.store'), [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => true,
            'projectName' => 'Neue Produktion',
            'eventNameMandatory' => false,
            'eventTypeId' => EventType::factory()->create()->id,
            'roomId' => $room->id,
            'title' => 'Probe',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
        ])->assertSuccessful();

        $project = Project::query()->where('name', 'Neue Produktion')->sole();
        $this->assertSame($project->id, Event::query()->where('room_id', $room->id)->sole()->project_id);
        $this->assertTrue(Table::query()->where('project_id', $project->id)->exists());
    }

    #[Test]
    public function event_creators_may_assign_any_project_even_without_being_in_its_team(): void
    {
        // Entscheidung 06.10.2026: Projektzuordnung im Termin-Dialog nicht einschränken
        $this->actingAsUserWith([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST]);
        $room = Room::factory()->create(['everyone_can_book' => true]);
        $foreignProject = Project::factory()->create();
        $eventType = EventType::factory()->create();
        $payload = [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'projectId' => $foreignProject->id,
            'projectName' => '',
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'roomId' => $room->id,
            'title' => 'Probe',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
        ];

        $this->postJson(route('events.store'), $payload)->assertSuccessful();
        $event = Event::query()->where('room_id', $room->id)->sole();
        $this->assertSame($foreignProject->id, $event->project_id);

        $otherProject = Project::factory()->create();
        $this->putJson(route('events.update', $event), [
            ...$payload,
            'projectId' => $otherProject->id,
            'noNotifications' => true,
        ])->assertSuccessful();
        $this->assertSame($otherProject->id, $event->fresh()->project_id);
    }

    #[Test]
    public function a_new_project_from_an_event_is_rejected_when_disabled_in_the_calendar_settings(): void
    {
        $this->disableProjectCreationFromEvents();
        $this->actingAsAdmin();
        $room = Room::factory()->create(['everyone_can_book' => true]);

        $this->postJson(route('events.store'), [
            ...$this->eventPayload($room),
            'creatingProject' => true,
            'projectName' => 'Neue Produktion',
        ])->assertJsonValidationErrors('projectName');

        $this->assertFalse(Project::query()->where('name', 'Neue Produktion')->exists());
        $this->assertFalse(Event::query()->where('room_id', $room->id)->exists());
    }

    #[Test]
    public function a_new_project_is_also_rejected_when_updating_an_event_with_project_creation_disabled(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create(['everyone_can_book' => true]);
        $this->postJson(route('events.store'), $this->eventPayload($room))->assertSuccessful();
        $event = Event::query()->where('room_id', $room->id)->sole();

        $this->disableProjectCreationFromEvents();
        $this->putJson(route('events.update', $event), [
            ...$this->eventPayload($room),
            'creatingProject' => true,
            'projectName' => 'Nachträgliche Produktion',
            'noNotifications' => true,
        ])->assertJsonValidationErrors('projectName');

        $this->assertFalse(Project::query()->where('name', 'Nachträgliche Produktion')->exists());
        $this->assertNull($event->fresh()->project_id);
    }

    #[Test]
    public function linking_an_existing_project_still_works_when_project_creation_is_disabled(): void
    {
        $this->disableProjectCreationFromEvents();
        $this->actingAsAdmin();
        $room = Room::factory()->create(['everyone_can_book' => true]);
        $project = Project::factory()->create();

        $this->postJson(route('events.store'), [
            ...$this->eventPayload($room),
            'projectId' => $project->id,
        ])->assertSuccessful();

        $this->assertSame($project->id, Event::query()->where('room_id', $room->id)->sole()->project_id);
    }

    private function disableProjectCreationFromEvents(): void
    {
        $calendarSettings = app(GeneralCalendarSettings::class);
        $calendarSettings->project_creation_from_events_enabled = false;
        $calendarSettings->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(Room $room): array
    {
        return [
            'start' => '2026-11-10 10:00',
            'end' => '2026-11-10 12:00',
            'projectIdMandatory' => false,
            'creatingProject' => false,
            'projectName' => '',
            'eventNameMandatory' => false,
            'eventTypeId' => EventType::factory()->create()->id,
            'roomId' => $room->id,
            'title' => 'Probe',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
        ];
    }
}
