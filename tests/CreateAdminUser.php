<?php

namespace Tests;

use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Spatie\Permission\Models\Role;

trait CreateAdminUser
{
    public function adminUser(User $user = null): User
    {
        $user = $user ?? User::factory()->create();
        Role::firstOrCreate(['name' => RoleEnum::ARTWORK_ADMIN->value]);
        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        return $user;
    }
}
