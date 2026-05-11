<?php

namespace Tests\Integration\Flows;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * End-to-end flow: Admin creates a project, attaches events through the
 * HTTP layer, and verifies the project owns the events.
 */
final class CreateProjectWithEventsFlowTest extends FeatureTestCase
{
    #[Test]
    public function admin_can_create_project_with_event_and_see_event_in_relation(): void
    {
        $admin = $this->actingAsAdmin();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create();

        // Step 1: Project via projects.store
        $projectResponse = $this->post(route('projects.store'), [
            'name' => 'Flow Project',
            'isGroup' => false,
        ]);
        $projectResponse->assertRedirect();

        /** @var Project $project */
        $project = Project::query()->where('name', 'Flow Project')->firstOrFail();

        // Step 2: Event via events.store linked to project
        $eventResponse = $this->postJson(route('events.store'), [
            'start' => '2026-05-12 10:00',
            'end' => '2026-05-12 12:00',
            'projectIdMandatory' => true,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'projectId' => $project->id,
            'roomId' => $room->id,
            'title' => 'Linked Event',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
        ]);

        $eventResponse->assertSuccessful();

        // Step 3: project.events() relation contains the event
        $project = $project->fresh('events');
        $this->assertGreaterThanOrEqual(1, $project->events->count());
        $this->assertDatabaseHas('events', [
            'project_id' => $project->id,
            'name' => 'Linked Event',
            'event_type_id' => $eventType->id,
            'room_id' => $room->id,
        ]);
    }

    #[Test]
    public function admin_can_attach_multiple_events_to_the_same_project(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create();

        $this->post(route('projects.store'), [
            'name' => 'Multi Event Project',
            'isGroup' => false,
        ])->assertRedirect();

        /** @var Project $project */
        $project = Project::query()->where('name', 'Multi Event Project')->firstOrFail();

        foreach (['Event A', 'Event B', 'Event C'] as $offset => $title) {
            $this->postJson(route('events.store'), [
                'start' => sprintf('2026-06-%02d 10:00', 10 + $offset),
                'end' => sprintf('2026-06-%02d 12:00', 10 + $offset),
                'projectIdMandatory' => true,
                'creatingProject' => false,
                'eventNameMandatory' => false,
                'eventTypeId' => $eventType->id,
                'projectId' => $project->id,
                'roomId' => $room->id,
                'title' => $title,
                'isOption' => false,
                'audience' => false,
                'isLoud' => false,
                'allDay' => false,
                'is_series' => false,
                'isPlanning' => false,
            ])->assertSuccessful();
        }

        $project = $project->fresh('events');
        $this->assertCount(
            3,
            $project->events->whereIn('name', ['Event A', 'Event B', 'Event C'])
        );
    }

    #[Test]
    public function event_added_to_project_persists_to_database(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();
        $room = Room::factory()->create();

        $this->post(route('projects.store'), [
            'name' => 'Persistence Project',
            'isGroup' => false,
        ])->assertRedirect();

        /** @var Project $project */
        $project = Project::query()->where('name', 'Persistence Project')->firstOrFail();

        $response = $this->postJson(route('events.store'), [
            'start' => '2026-07-01 09:00',
            'end' => '2026-07-01 11:00',
            'projectIdMandatory' => true,
            'creatingProject' => false,
            'eventNameMandatory' => false,
            'eventTypeId' => $eventType->id,
            'projectId' => $project->id,
            'roomId' => $room->id,
            'title' => 'Persisted Event',
            'isOption' => false,
            'audience' => false,
            'isLoud' => false,
            'allDay' => false,
            'is_series' => false,
            'isPlanning' => false,
        ]);

        $response->assertSuccessful();

        $event = Event::query()->where('name', 'Persisted Event')->first();
        $this->assertNotNull($event, 'Event should be stored in DB');
        $this->assertSame($project->id, $event->project_id);
    }
}
