<?php

namespace Tests\Feature\PropShape;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Timeline\Models\Timeline;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsPropShape;
use Tests\Feature\FeatureTestCase;

/**
 * N+1-Wächter für zwei Sentry-Funde (22.09.2026): Komponentenwerte in der Projektübersicht
 * (eine Query pro Projekt × Komponente) und timelines-exists in der Bulk-Bearbeitung
 * (eine Query pro Termin).
 */
final class ProjectIndexBulkEditQueryGuardTest extends FeatureTestCase
{
    use AssertsPropShape;

    #[Test]
    public function project_index_loads_component_values_without_per_project_queries(): void
    {
        $this->actingAsAdmin();

        $components = collect(range(1, 3))->map(fn (int $i) => Component::create([
            'name' => 'Textfeld ' . $i,
            'type' => 'TextField',
            'data' => [],
            'special' => false,
        ]));
        foreach ($components as $index => $component) {
            ProjectManagementBuilder::create([
                'name' => $component->name,
                'order' => $index + 1,
                'is_active' => true,
                'type' => 'ProjectComponent',
                'deletable' => true,
                'component_id' => $component->id,
            ]);
        }

        $projects = Project::factory()->count(6)->create();
        foreach ($projects as $project) {
            foreach ($components as $component) {
                ProjectComponentValue::create([
                    'component_id' => $component->id,
                    'project_id' => $project->id,
                    'data' => ['text' => 'Wert ' . $project->id],
                ]);
            }
        }

        $response = $this->assertNoRepeatedQueryPatterns(
            fn () => $this->get(route('projects', ['entitiesPerPage' => 10]))->assertOk(),
            4
        );

        $response->assertInertia(fn ($page) => $page
            ->has('projectComponents', 6)
            ->has('projectComponents.0.ProjectComponent', 3));
    }

    #[Test]
    public function bulk_edit_resolves_has_timelines_without_per_event_queries(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $room = Room::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $event = Event::factory()->create([
                'project_id' => $project->id,
                'room_id' => $room->id,
                'start_time' => '2026-04-10 10:00:00',
                'end_time' => '2026-04-10 12:00:00',
            ]);
            if ($i % 2 === 0) {
                Timeline::create([
                    'event_id' => $event->id,
                    'start_date' => '2026-04-10',
                    'end_date' => '2026-04-10',
                    'start' => '10:00',
                    'end' => '11:00',
                    'description' => 'Aufbau',
                ]);
            }
        }

        $this->assertNoRepeatedQueryPatterns(
            fn () => $this->getJson(route('projects.tabs.bulk-edit', $project))->assertOk(),
            4
        );
    }
}
