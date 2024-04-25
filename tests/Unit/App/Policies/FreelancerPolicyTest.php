<?php

namespace Tests\Unit\App\Policies;

use Artwork\Modules\Freelancer\Policies\FreelancerPolicy;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class FreelancerPolicyTest extends TestCase
{
    public function testUpdateWorkProfile(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::MA_MANAGER->value);

        $policy = new FreelancerPolicy();

        $this->assertTrue($policy->updateWorkProfile($user));
    }

    public function testUpdateTerms(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::MA_MANAGER->value);

        $policy = new FreelancerPolicy();

        $this->assertTrue($policy->updateTerms($user));
    }
}
