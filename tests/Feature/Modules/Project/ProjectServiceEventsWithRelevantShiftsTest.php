<?php

namespace Tests\Feature\Modules\Project;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regression für den Eager-Loading-Umbau von ProjectService::getEventsWithRelevantShifts:
 * Payload-Parität (craft/committedBy/Personen an den Schichten, Timeline-Sortierung,
 * Raum ohne admins/creator) ohne die früheren Requeries pro Event/Schicht/Person.
 */
final class ProjectServiceEventsWithRelevantShiftsTest extends FeatureTestCase
{
    private function service(): ProjectService
    {
        return app(ProjectService::class);
    }

    private function makeProjectWithRelevantEventType(): array
    {
        $project = Project::factory()->create();
        $eventType = EventType::factory()->create();
        $project->shiftRelevantEventTypes()->attach($eventType->id);

        return [$project, $eventType];
    }

    #[Test]
    public function only_events_with_shift_relevant_event_types_are_returned(): void
    {
        $this->actingAs($this->adminUser());
        [$project, $relevantType] = $this->makeProjectWithRelevantEventType();
        $otherType = EventType::factory()->create();

        $relevantEvent = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $relevantType->id,
        ]);
        Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $otherType->id,
        ]);

        $result = $this->service()->getEventsWithRelevantShifts($project->id);

        $this->assertCount(1, $result);
        $this->assertSame($relevantEvent->id, $result[0]['event']->id);
    }

    #[Test]
    public function shifts_carry_craft_committed_by_and_workers_without_lazy_loading(): void
    {
        $this->actingAs($this->adminUser());
        [$project, $eventType] = $this->makeProjectWithRelevantEventType();

        $event = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $eventType->id,
        ]);
        $craft = Craft::factory()->create(['name' => 'Technik']);
        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'craft_id' => $craft->id,
        ]);
        $qualification = ShiftQualification::factory()->create();
        $worker = User::factory()->create();
        $shift->users()->attach($worker->id, [
            'shift_qualification_id' => $qualification->id,
            'shift_count' => 1,
        ]);

        $result = $this->service()->getEventsWithRelevantShifts($project->id);

        $resultShift = $result[0]['shifts']->first();
        $this->assertNotNull($resultShift);

        // Payload-Parität nach Entfernung des globalen Shift-$with: diese Relationen
        // müssen eager geladen sein, sonst fehlen sie im serialisierten Schicht-Tab
        $this->assertTrue($resultShift->relationLoaded('craft'));
        $this->assertTrue($resultShift->relationLoaded('committedBy'));
        $this->assertTrue($resultShift->relationLoaded('users'));
        $this->assertTrue($resultShift->relationLoaded('freelancer'));
        $this->assertTrue($resultShift->relationLoaded('serviceProvider'));
        $this->assertTrue($resultShift->relationLoaded('shiftsQualifications'));
        $this->assertSame('Technik', $resultShift->craft->name);

        $resultWorker = $resultShift->users->first();
        $this->assertNotNull($resultWorker);
        $this->assertTrue($resultWorker->relationLoaded('globalQualifications'));
        $this->assertTrue($resultWorker->relationLoaded('vacations'));
        $this->assertIsArray($resultWorker->formatted_vacation_days);
    }

    #[Test]
    public function timelines_are_sorted_and_room_is_loaded_without_admins_and_creator(): void
    {
        $this->actingAs($this->adminUser());
        [$project, $eventType] = $this->makeProjectWithRelevantEventType();

        $room = Room::factory()->create();
        $event = Event::factory()->create([
            'project_id' => $project->id,
            'event_type_id' => $eventType->id,
            'room_id' => $room->id,
        ]);

        $later = Timeline::factory()->create([
            'event_id' => $event->id,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'description' => '<p>Später</p>',
        ]);
        $earlier = Timeline::factory()->create([
            'event_id' => $event->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'description' => '<p>Früher</p>',
        ]);

        $result = $this->service()->getEventsWithRelevantShifts($project->id);
        $timeline = $result[0]['timeline'];

        $this->assertSame([$earlier->id, $later->id], array_column($timeline, 'id'));
        // strip_tags-Aufbereitung bleibt erhalten
        $this->assertSame('Früher', $timeline[0]['description_without_html']);

        $resultRoom = $result[0]['room'];
        $this->assertSame($room->id, $resultRoom->id);
        $this->assertFalse($resultRoom->relationLoaded('admins'));
        $this->assertFalse($resultRoom->relationLoaded('creator'));
    }
}
