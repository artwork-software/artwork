<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
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

    private function patchComponentValue(Project $project, Component $component)
    {
        return $this->patch(route('project.tab.component.update', [
            'project' => $project->id,
            'component' => $component->id,
        ]), ['data' => ['text' => 'Hello']]);
    }

    #[Test]
    public function user_without_project_access_cannot_update_component_value(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $component = Component::create(['name' => 'X', 'type' => 'TextArea', 'data' => []]);

        $this->patchComponentValue($project, $component)->assertForbidden();
        $this->assertDatabaseMissing('project_component_values', ['project_id' => $project->id]);
    }

    #[Test]
    public function user_with_view_permission_can_update_all_see_and_edit_component(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $project = Project::factory()->create();
        $component = Component::create([
            'name' => 'X',
            'type' => 'TextArea',
            'data' => [],
            'permission_type' => 'allSeeAndEdit',
        ]);

        $response = $this->patchComponentValue($project, $component);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('project_component_values', [
            'project_id' => $project->id,
            'component_id' => $component->id,
        ]);
    }

    #[Test]
    public function user_with_view_permission_cannot_update_restricted_component(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $project = Project::factory()->create();
        $component = Component::create([
            'name' => 'X',
            'type' => 'TextArea',
            'data' => [],
            'permission_type' => 'someSeeSomeEdit',
        ]);

        $this->patchComponentValue($project, $component)->assertForbidden();
        $this->assertDatabaseMissing('project_component_values', ['project_id' => $project->id]);
    }

    #[Test]
    public function write_projects_permission_overrides_component_restriction(): void
    {
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $project = Project::factory()->create();
        $component = Component::create([
            'name' => 'X',
            'type' => 'TextArea',
            'data' => [],
            'permission_type' => 'someSeeSomeEdit',
        ]);

        $response = $this->patchComponentValue($project, $component);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('project_component_values', [
            'project_id' => $project->id,
            'component_id' => $component->id,
        ]);
    }

    #[Test]
    public function project_team_member_can_update_all_see_and_edit_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $component = Component::create([
            'name' => 'X',
            'type' => 'TextArea',
            'data' => [],
            'permission_type' => 'allSeeAndEdit',
        ]);

        $response = $this->patchComponentValue($project, $component);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('project_component_values', [
            'project_id' => $project->id,
            'component_id' => $component->id,
        ]);
    }

    #[Test]
    public function component_circle_member_with_can_write_can_update_restricted_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $component = Component::create([
            'name' => 'X',
            'type' => 'TextArea',
            'data' => [],
            'permission_type' => 'someSeeSomeEdit',
        ]);
        $component->users()->attach($user->id, ['can_write' => true]);

        $response = $this->patchComponentValue($project, $component);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('project_component_values', [
            'project_id' => $project->id,
            'component_id' => $component->id,
        ]);
    }

    #[Test]
    public function component_circle_member_without_can_write_cannot_update_restricted_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $component = Component::create([
            'name' => 'X',
            'type' => 'TextArea',
            'data' => [],
            'permission_type' => 'someSeeSomeEdit',
        ]);
        $component->users()->attach($user->id, ['can_write' => false]);

        $this->patchComponentValue($project, $component)->assertForbidden();
        $this->assertDatabaseMissing('project_component_values', ['project_id' => $project->id]);
    }
}
