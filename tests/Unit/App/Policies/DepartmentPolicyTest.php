<?php

namespace Tests\Unit\App\Policies;

use Artwork\Modules\Department\Policies\DepartmentPolicy;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class DepartmentPolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->viewAny($user));
    }

    public function testView(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->view($user));
    }

    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->update($user));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->delete($user));
    }
}
