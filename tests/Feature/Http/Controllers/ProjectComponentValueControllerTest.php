<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectComponentValueControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update_project_component_value(): void
    {
        $project = Project::factory()->create();
        $component = Component::create(['name' => 'X', 'type' => 'TextArea', 'data' => []]);

        $this->patch(route('project.tab.component.update', [
            'project' => $project->id,
            'component' => $component->id,
        ]), ['data' => ['text' => 'Hello']])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_project_component_value(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $component = Component::create(['name' => 'X', 'type' => 'TextArea', 'data' => []]);

        $response = $this->patch(
            route('project.tab.component.update', [
                'project' => $project->id,
                'component' => $component->id,
            ]),
            ['data' => ['text' => 'Hello World']]
        );

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('project_component_values', [
            'project_id' => $project->id,
            'component_id' => $component->id,
        ]);
    }
}
