<?php

namespace Tests\Unit\App\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\ServiceProvider\Policies\ServiceProviderPolicy;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class ServiceProviderPolicyTest extends TestCase
{
    public function testUpdateWorkProfile(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::MA_MANAGER->value);

        $policy = new ServiceProviderPolicy();

        $this->assertTrue($policy->updateWorkProfile($user));
    }

    public function testUpdateTerms(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::MA_MANAGER->value);

        $policy = new ServiceProviderPolicy();

        $this->assertTrue($policy->updateTerms($user));
    }
}
