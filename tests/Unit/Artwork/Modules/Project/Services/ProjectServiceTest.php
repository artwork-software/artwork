<?php

namespace Tests\Unit\Artwork\Modules\Project\Services;

use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    private ProjectService $projectService;
    protected function setUp(): void
    {
        parent::setUp();
        $this->projectService = $this->app->make(ProjectService::class);
    }

    public function testIsManagerForProject(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $project->users()->attach($user->id, ['is_manager' => true]);

        $this->assertTrue($this->projectService->isManagerForProject($user, $project));
    }

    public function testGetProjectByCostCenter(): void
    {
        $costCenter = CostCenter::factory()->create();
        $project = Project::factory()->create();

        $project->update(['cost_center_id' => $costCenter->id]);

        $this->assertEquals($project->id, $this->projectService->getProjectByCostCenter($costCenter->name)->id);
    }

    public function testGetProjects(): void
    {
        $createCount = 5;
        $currentProjectCount = $this->projectService->getProjects()->count();

        Project::factory()->count($createCount)->create();

        $this->assertEquals(($currentProjectCount + $createCount), $this->projectService->getProjects()->count());
    }

    public function testPin(): void
    {
        $project = Project::factory()->create();
        // fake auth
        $this->actingAs(User::factory()->create());
        $this->projectService->pin($project);
        // check if user is in column pinned_by_users array
        $this->assertContains(auth()->user()->id, $project->pinned_by_users);
    }

    public function testGetUsersForProject(): void
    {
        $project = Project::factory()->create();
        $users = User::factory()->count(5)->create();
        $project->users()->attach($users->pluck('id')->toArray());
        $this->assertEquals(5, $this->projectService->getUsersForProject($project)->count());
    }

    public function testFindById(): void
    {
        $project = Project::factory()->create();
        $this->assertEquals($project->id, $this->projectService->findById($project->id)->id);
    }

    public function testSave(): void
    {
        $project = Project::factory()->make();
        $this->projectService->save($project);
        $this->assertDatabaseHas('projects', ['name' => $project->name]);
    }

    public function testGetAll(): void
    {
        $createCount = 5;
        $currentProjectCount = $this->projectService->getProjects()->count();

        Project::factory()->count($createCount)->create();

        $this->assertEquals(($currentProjectCount + $createCount), $this->projectService->getAll()->count());
    }

    public function testGetByName(): void
    {
        $project = Project::factory()->create();
        $projectFound = $this->projectService->getByName($project->name)->first();
        $this->assertEquals($project->name, $projectFound->name);
    }

    public function testUpdateShiftContact(): void
    {
        $project = Project::factory()->create();
        $project->shift_contact()->attach(User::factory()->create());
        $time = now()->subMinutes(10)->format('Y-m-d H:i:s');
        $this->projectService->updateShiftContact($project, $time);
        // check if all $project->shiftRelevantEventTypes relations deleted_at is same as $time
        $this->assertDatabaseHas('project_shift_contacts', ['project_id' => $project->id, 'deleted_at' => $time]);
    }
}
