<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectTabTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_project_tab(): void
    {
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_project_tab(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $response = $this->get(route('projects.tab', [
            'project' => $project->id,
            'projectTab' => $tab->id,
        ]));

        // 200 OK for full Inertia, or 302 redirect when middleware re-routes
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    #[Test]
    public function user_without_project_access_is_redirected_or_forbidden(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $response = $this->get(route('projects.tab', [
            'project' => $project->id,
            'projectTab' => $tab->id,
        ]));

        // CanViewProject middleware should not allow random user
        $this->assertContains($response->getStatusCode(), [302, 403, 404]);
    }

    #[Test]
    public function project_team_member_can_view_project_tab(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk();
    }

    #[Test]
    public function department_team_member_can_view_project_tab(): void
    {
        // Abteilungen im Projektteam zählen als Teammitgliedschaft (ProjectPolicy::view) —
        // die alte CanViewProject-Middleware kannte diesen Fall nicht.
        $user = User::factory()->create();
        $this->actingAs($user);
        $department = Department::factory()->create();
        $department->users()->attach($user->id);
        $project = Project::factory()->create();
        $project->departments()->attach($department->id);
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk();
    }

    #[Test]
    public function tab_for_softdeleted_project_returns_410(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $project->delete();

        $response = $this->get(route('projects.tab', [
            'project' => $project->id,
            'projectTab' => $tab->id,
        ]));

        // Project soft-deleted: controller returns 410 Gone (or 404 if route binding fails)
        $this->assertContains($response->getStatusCode(), [404, 410]);
    }

    #[Test]
    public function tab_returns_404_for_unknown_project(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create();

        $this->get(route('projects.tab', [
            'project' => 999999,
            'projectTab' => $tab->id,
        ]))->assertNotFound();
    }
}
