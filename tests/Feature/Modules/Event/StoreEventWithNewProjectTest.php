<?php

namespace Tests\Feature\Modules\Event;

use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
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
}
