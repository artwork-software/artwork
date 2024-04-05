<?php

namespace Tests;

use App\Enums\RoleNameEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;
    use WithFaker;

    /**
     * @return \App\Models\User
     */
    public function adminUser(User $user = null): User
    {
        $user = $user ?? User::factory()->create();
        Role::firstOrCreate(['name' => RoleNameEnum::ARTWORK_ADMIN->value]);
        $user->assignRole(RoleNameEnum::ARTWORK_ADMIN->value);

        return $user;
    }

}
