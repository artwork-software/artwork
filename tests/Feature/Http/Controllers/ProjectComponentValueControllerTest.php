<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Regelwerk (ProjectPolicy::writeComponent): Schreibrecht im Projekt ist Grundvoraussetzung,
 * die Komponenten-Einstellung kann nur weiter einschränken. Globales "write projects" übersteuert.
 */
final class ProjectComponentValueControllerTest extends FeatureTestCase
{
    private function makeComponent(?string $permissionType = 'allSeeAndEdit'): Component
    {
        return Component::create([
            'name' => 'X',
            'type' => 'TextArea',
            'data' => [],
            'permission_type' => $permissionType,
        ]);
    }

    private function patchComponentValue(Project $project, Component $component)
    {
        return $this->patch(route('project.tab.component.update', [
            'project' => $project->id,
            'component' => $component->id,
        ]), ['data' => ['text' => 'Hello']]);
    }

    private function assertValueStored(Project $project, Component $component, $response): void
    {
        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('project_component_values', [
            'project_id' => $project->id,
            'component_id' => $component->id,
        ]);
    }

    private function assertValueRejected(Project $project, $response): void
    {
        $response->assertForbidden();
        $this->assertDatabaseMissing('project_component_values', ['project_id' => $project->id]);
    }

    #[Test]
    public function guest_cannot_update_project_component_value(): void
    {
        $project = Project::factory()->create();
        $component = $this->makeComponent();

        $this->patchComponentValue($project, $component)->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_project_component_value(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $component = $this->makeComponent();

        $this->assertValueStored($project, $component, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function user_without_project_access_cannot_update_component_value(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $component = $this->makeComponent();

        $this->assertValueRejected($project, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function global_read_permission_does_not_allow_editing_open_component(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $project = Project::factory()->create();
        $component = $this->makeComponent('allSeeAndEdit');

        $this->assertValueRejected($project, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function project_management_permission_alone_does_not_allow_editing_foreign_project(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_MANAGEMENT->value);
        $project = Project::factory()->create();
        $component = $this->makeComponent('allSeeAndEdit');

        $this->assertValueRejected($project, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function write_projects_permission_overrides_component_restriction(): void
    {
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $project = Project::factory()->create();
        $component = $this->makeComponent('someSeeSomeEdit');

        $this->assertValueStored($project, $component, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function team_member_without_write_flag_cannot_update_open_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => false]);
        $component = $this->makeComponent('allSeeAndEdit');

        $this->assertValueRejected($project, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function team_member_with_write_flag_can_update_open_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);
        $component = $this->makeComponent('allSeeAndEdit');

        $this->assertValueStored($project, $component, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function newly_attached_team_member_gets_write_flag_by_default(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $component = $this->makeComponent('allSeeAndEdit');

        $this->assertValueStored($project, $component, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function project_manager_can_update_open_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => false, 'is_manager' => true]);
        $component = $this->makeComponent('allSeeAndEdit');

        $this->assertValueStored($project, $component, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function department_member_can_update_open_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $department = Department::factory()->create();
        $department->users()->attach($user->id);
        $project = Project::factory()->create();
        $project->departments()->attach($department->id);
        $component = $this->makeComponent('allSeeAndEdit');

        $this->assertValueStored($project, $component, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function component_restriction_cannot_extend_project_rights(): void
    {
        // Im Komponenten-Kreis mit Schreib-Häkchen, aber ohne Schreibrecht im Projekt → kein Schreiben
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => false]);
        $component = $this->makeComponent('someSeeSomeEdit');
        $component->users()->attach($user->id, ['can_write' => true]);

        $this->assertValueRejected($project, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function component_circle_member_with_can_write_can_update_restricted_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);
        $component = $this->makeComponent('someSeeSomeEdit');
        $component->users()->attach($user->id, ['can_write' => true]);

        $this->assertValueStored($project, $component, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function component_circle_member_without_can_write_cannot_update_restricted_component(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);
        $component = $this->makeComponent('someSeeSomeEdit');
        $component->users()->attach($user->id, ['can_write' => false]);

        $this->assertValueRejected($project, $this->patchComponentValue($project, $component));
    }

    #[Test]
    public function write_team_member_outside_all_see_some_edit_circle_cannot_update(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);
        $component = $this->makeComponent('allSeeSomeEdit');

        $this->assertValueRejected($project, $this->patchComponentValue($project, $component));
    }
}
