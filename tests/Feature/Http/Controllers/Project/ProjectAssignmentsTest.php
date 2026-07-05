<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectAssignmentsTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update_team(): void
    {
        $project = Project::factory()->create();

        $this->patch('/projects/' . $project->id . '/team', [
            'assigned_user_ids' => [],
            'assigned_departments' => [],
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_team_with_users(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $response = $this->patch('/projects/' . $project->id . '/team', [
            'assigned_user_ids' => [$u1->id, $u2->id],
            'assigned_departments' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $u1->id,
        ]);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $u2->id,
        ]);
    }

    #[Test]
    public function update_team_persists_only_existing_project_role_ids(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $role = ProjectRole::factory()->create();

        $response = $this->patch('/projects/' . $project->id . '/team', [
            'assigned_user_ids' => [
                $user->id => [
                    'access_budget' => false,
                    'is_manager' => false,
                    'can_write' => false,
                    'delete_permission' => false,
                    'roles' => [$role->id, 999999],
                ],
            ],
            'assigned_departments' => [],
        ]);

        $response->assertRedirect();
        $pivotRoles = json_decode(
            DB::table('project_user')
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->value('roles'),
            true
        );
        $this->assertSame([$role->id], $pivotRoles);
    }

    #[Test]
    public function unauthorized_user_cannot_update_team(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->patchJson('/projects/' . $project->id . '/team', [
            'assigned_user_ids' => [],
            'assigned_departments' => [],
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_update_attributes_with_categories(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->patch('/projects/' . $project->id . '/attributes', [
            'assignedCategoryIds' => [],
            'assignedGenreIds' => [],
            'assignedSectorIds' => [],
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function guest_cannot_update_attributes(): void
    {
        $project = Project::factory()->create();

        $this->patch('/projects/' . $project->id . '/attributes', [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_call_delete_project_from_group_endpoint(): void
    {
        $this->actingAsAdmin();
        $group = Project::factory()->create(['is_group' => true]);
        $project = Project::factory()->create();
        $group->projectsOfGroup()->attach($project->id);

        // Returns void -> 200 status. Just assert the endpoint is reachable.
        $response = $this->delete('/project/group/' . $project->id . '/' . $group->id);
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    #[Test]
    public function admin_can_add_projects_to_group(): void
    {
        $this->actingAsAdmin();
        $group = Project::factory()->create(['is_group' => false]);
        $child = Project::factory()->create();

        $this->post('/project/group/' . $group->id . '/add/projects', [
            'projectIdsToAdd' => [['id' => $child->id]],
        ]);

        $this->assertTrue((bool) $group->fresh()->is_group);
    }

    #[Test]
    public function admin_can_update_shift_contacts(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->patch('/projects/' . $project->id . '/shiftContacts', [
            'shift_contact' => [],
        ]);

        $response->assertOk();
    }

    #[Test]
    public function admin_can_update_shift_relevant_event_types(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->patch('/projects/' . $project->id . '/shiftRelevantEventTypes', [
            'shift_relevant_event_types' => [],
        ]);

        $response->assertOk();
    }

    #[Test]
    public function admin_can_update_cost_center(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post('/project/' . $project->id . '/cost-center/update', [
            'cost_center' => 'CC-123',
            'description' => 'desc',
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_view_project_user_search(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('project.user.search', [
            'query' => 'foo',
            'projectId' => $project->id,
        ]));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }
}
