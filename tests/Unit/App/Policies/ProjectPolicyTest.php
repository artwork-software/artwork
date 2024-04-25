<?php

namespace Tests\Unit\App\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Policies\ProjectPolicy;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $policy = new ProjectPolicy();

        $this->assertTrue($policy->viewAny());
    }

    public function testView(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $policy = new ProjectPolicy();

        $this->assertFalse($policy->view($user, $project));
    }

    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);

        $policy = new ProjectPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testCreateProperties(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $policy = new ProjectPolicy();

        $this->assertFalse($policy->createProperties($user, $project));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $policy = new ProjectPolicy();

        $this->assertFalse($policy->update($user, $project));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $policy = new ProjectPolicy();

        $this->assertFalse($policy->delete($user, $project));
    }
}
