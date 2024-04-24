<?php

namespace Tests\Unit\App\Policies;

use App\Enums\PermissionNameEnum;
use App\Policies\UserPolicy;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $policy = new UserPolicy();

        $this->assertTrue($policy->viewAny());
    }

    public function testView(): void
    {
        $policy = new UserPolicy();

        $this->assertTrue($policy->view());
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $policy = new UserPolicy();

        $this->assertTrue($policy->update($user));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $model = clone $user;

        $policy = new UserPolicy();

        $this->assertTrue($policy->delete($user, $model));
    }

    public function testUpdateWorkProfile(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::MA_MANAGER->value);

        $policy = new UserPolicy();

        $this->assertTrue($policy->updateWorkProfile($user));
    }

    public function testUpdateTerms(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::MA_MANAGER->value);

        $policy = new UserPolicy();

        $this->assertTrue($policy->updateTerms($user));
    }
}
