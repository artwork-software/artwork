<?php

namespace Tests\Unit\App\Policies;

use Artwork\Modules\Genre\Policies\GenrePolicy;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class GenrePolicyTest extends TestCase
{
    public function testViewAny(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $policy = new GenrePolicy();

        $this->assertTrue($policy->viewAny($user));
    }

    public function testCreate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $policy = new GenrePolicy();

        $this->assertTrue($policy->create($user));
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $policy = new GenrePolicy();

        $this->assertTrue($policy->update($user));
    }

    public function testDelete(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $policy = new GenrePolicy();

        $this->assertTrue($policy->delete($user));
    }
}
