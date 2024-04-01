<?php

namespace Tests\Unit\App\Policies;

use App\Models\User;
use App\Policies\ServiceProviderPolicy;
use App\Enums\PermissionNameEnum;
use Tests\TestCase;

class ServiceProviderPolicyTest extends TestCase
{
    public function testUpdateWorkProfile(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::MA_MANAGER->value);

        $policy = new ServiceProviderPolicy();

        $this->assertTrue($policy->updateWorkProfile($user));
    }

    public function testUpdateTerms(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::MA_MANAGER->value);

        $policy = new ServiceProviderPolicy();

        $this->assertTrue($policy->updateTerms($user));
    }
}
