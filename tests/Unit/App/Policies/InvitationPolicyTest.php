<?php

namespace Tests\Unit\App\Policies;

use App\Models\User;
use App\Policies\InvitationPolicy;
use App\Enums\PermissionNameEnum;
use Tests\TestCase;

class InvitationPolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::MA_MANAGER->value);

        $policy = new InvitationPolicy();

        $this->assertTrue($policy->viewAny($user));
    }

    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::MA_MANAGER->value);

        $policy = new InvitationPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::MA_MANAGER->value);

        $policy = new InvitationPolicy();

        $this->assertTrue($policy->update($user));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::MA_MANAGER->value);

        $policy = new InvitationPolicy();

        $this->assertTrue($policy->delete($user));
    }
}
