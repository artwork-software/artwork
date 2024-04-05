<?php

namespace Tests\Unit\App\Policies;

use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Enums\PermissionNameEnum;
use Tests\TestCase;

class DepartmentPolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->viewAny($user));
    }

    public function testView(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->view($user));
    }

    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->update($user));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::TEAM_UPDATE->value);

        $policy = new DepartmentPolicy();

        $this->assertTrue($policy->delete($user));
    }
}
