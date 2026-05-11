<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectServiceTest extends TestCase
{
    private ProjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectService::class);
    }

    #[Test]
    public function save_persists_project_and_returns_it(): void
    {
        $project = Project::factory()->make();

        $saved = $this->service->save($project);

        $this->assertNotNull($saved->id);
        $this->assertDatabaseHas('projects', ['id' => $saved->id]);
    }

    #[Test]
    public function find_by_id_returns_project(): void
    {
        $project = Project::factory()->create();

        $found = $this->service->findById($project->id);

        $this->assertSame($project->id, $found->id);
    }

    #[Test]
    public function get_all_returns_eloquent_collection(): void
    {
        Project::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    #[Test]
    public function get_by_name_returns_matching_projects(): void
    {
        Project::factory()->create(['name' => 'Findable Project']);
        Project::factory()->create(['name' => 'Other']);

        $result = $this->service->getByName('Findable');

        $this->assertGreaterThanOrEqual(1, $result->count());
        $this->assertTrue($result->contains(fn ($p) => str_contains($p->name, 'Findable')));
    }

    #[Test]
    public function update_project_modifies_attributes(): void
    {
        $project = Project::factory()->create(['name' => 'Initial']);

        $updated = $this->service->updateProject($project, ['name' => 'Renamed']);

        $this->assertSame('Renamed', $updated->name);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Renamed']);
    }

    #[Test]
    public function is_manager_for_project_detects_manager(): void
    {
        $project = Project::factory()->create();
        $manager = User::factory()->create();
        $other = User::factory()->create();
        $this->service->attachManagementUsers($project, [$manager->id]);

        $this->assertTrue($this->service->isManagerForProject($manager, $project));
        $this->assertFalse($this->service->isManagerForProject($other, $project));
    }

    #[Test]
    public function attach_management_users_persists_pivot_rows(): void
    {
        $project = Project::factory()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $this->service->attachManagementUsers($project, [$u1->id, $u2->id]);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $u1->id,
            'is_manager' => 1,
        ]);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $u2->id,
            'is_manager' => 1,
        ]);
    }

    #[Test]
    public function attach_management_users_without_self_excludes_authenticated_user(): void
    {
        $project = Project::factory()->create();
        $self = User::factory()->create();
        $other = User::factory()->create();

        $this->service->attachManagementUsersWithoutSelf(
            $project,
            new SupportCollection([$self->id, $other->id]),
            $self->id
        );

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $other->id,
            'is_manager' => 1,
        ]);
        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $self->id,
        ]);
    }

    #[Test]
    public function attach_user_to_project_adds_pivot_row(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $this->service->attachUserToProject($project, $user->id, true);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_manager' => 1,
        ]);
    }

    #[Test]
    public function detach_management_users_removes_specific_users(): void
    {
        $project = Project::factory()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->service->attachManagementUsers($project, [$u1->id, $u2->id]);

        $this->service->detachManagementUsers($project, false, [$u1->id]);

        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $u1->id,
        ]);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $u2->id,
        ]);
    }

    #[Test]
    public function detach_management_users_detaching_all_clears_managers(): void
    {
        $project = Project::factory()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->service->attachManagementUsers($project, [$u1->id, $u2->id]);

        $this->service->detachManagementUsers($project, true);

        $this->assertSame(0, $project->managerUsers()->count());
    }

    #[Test]
    public function pin_toggles_pinned_state_for_authenticated_user(): void
    {
        $project = Project::factory()->create(['pinned_by_users' => null]);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->service->pin($project);
        $project->refresh();
        $this->assertContains($user->id, $project->pinned_by_users);

        $this->service->pin($project);
        $project->refresh();
        $this->assertNotContains($user->id, $project->pinned_by_users ?? []);
    }

    #[Test]
    public function get_users_for_project_returns_attached_users(): void
    {
        $project = Project::factory()->create();
        $u = User::factory()->create();
        $this->service->attachUserToProject($project, $u->id, false);

        $users = $this->service->getUsersForProject($project);

        $this->assertGreaterThanOrEqual(1, $users->count());
        $this->assertTrue($users->contains('id', $u->id));
    }

    #[Test]
    public function get_my_last_project_returns_most_recent_project_for_user(): void
    {
        $user = User::factory()->create();
        $older = Project::factory()->create(['user_id' => $user->id]);
        $newer = Project::factory()->create(['user_id' => $user->id]);
        // Ensure deterministic ordering by setting updated_at explicitly
        $older->forceFill(['updated_at' => now()->subDay()])->save();
        $newer->forceFill(['updated_at' => now()])->save();

        $latest = $this->service->getMyLastProject($user->id);

        $this->assertSame($newer->id, $latest->id);
    }

    #[Test]
    public function paginate_projects_returns_length_aware_paginator(): void
    {
        Project::factory()->count(3)->create();

        $result = $this->service->paginateProjects(
            search: '',
            perPage: 2,
            sortEnum: null,
            projectStateIds: null,
            projectFilters: new SupportCollection([]),
        );

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function get_non_project_group_by_name_returns_only_non_group_match(): void
    {
        Project::factory()->create(['name' => 'Single Project', 'is_group' => false]);
        Project::factory()->create(['name' => 'Single Group', 'is_group' => true]);

        $project = $this->service->getNonProjectGroupByName('Single');

        $this->assertNotNull($project);
        $this->assertFalse((bool) $project->is_group);
    }

    #[Test]
    public function get_project_group_by_name_returns_only_group(): void
    {
        Project::factory()->create(['name' => 'GroupCheck One', 'is_group' => true]);
        Project::factory()->create(['name' => 'GroupCheck Two', 'is_group' => false]);

        $group = $this->service->getProjectGroupByName('GroupCheck');

        $this->assertNotNull($group);
        $this->assertTrue((bool) $group->is_group);
    }
}
