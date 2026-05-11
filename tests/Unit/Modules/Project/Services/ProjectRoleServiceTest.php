<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectRoleService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectRoleServiceTest extends TestCase
{
    private ProjectRoleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectRoleService::class);
    }

    #[Test]
    public function create_by_request_persists_role(): void
    {
        $this->service->createByRequest(['name' => 'Director']);

        $this->assertDatabaseHas('project_roles', ['name' => 'Director']);
    }

    #[Test]
    public function update_by_request_modifies_existing_role(): void
    {
        $role = ProjectRole::factory()->create(['name' => 'Old']);

        $this->service->updateByRequest($role, ['name' => 'New']);

        $this->assertDatabaseHas('project_roles', ['id' => $role->id, 'name' => 'New']);
    }

    #[Test]
    public function delete_removes_role_from_database(): void
    {
        $role = ProjectRole::factory()->create();

        $this->service->delete($role);

        $this->assertDatabaseMissing('project_roles', ['id' => $role->id]);
    }
}
