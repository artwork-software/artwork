<?php

namespace Tests\Unit\App\Policies;

use App\Enums\PermissionNameEnum;
use App\Policies\ServiceProviderPolicy;
use Artwork\Modules\User\Models\User;
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
