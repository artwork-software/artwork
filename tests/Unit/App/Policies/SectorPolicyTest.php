<?php

namespace Tests\Unit\App\Policies;

use App\Enums\PermissionNameEnum;
use App\Policies\SectorPolicy;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class SectorPolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);

        $policy = new SectorPolicy();

        $this->assertTrue($policy->viewAny($user));
    }

    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);

        $policy = new SectorPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);

        $policy = new SectorPolicy();

        $this->assertTrue($policy->update($user));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);

        $policy = new SectorPolicy();

        $this->assertTrue($policy->delete($user));
    }
}
