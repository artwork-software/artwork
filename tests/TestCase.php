<?php

namespace Tests;

use Artwork\Modules\EventType\Cache\EventTypeArrayCache;
use Artwork\Modules\ProjectTab\Cache\ProjectTabArrayCache;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        EventTypeArrayCache::forgetAll();
        ProjectTabArrayCache::forgetAll();
        $this->withoutVite();
    }

    /**
     * @return \Artwork\Modules\User\Models\User
     */
    public function adminUser(User $user = null): User
    {
        $user = $user ?? User::factory()->create();
        Role::firstOrCreate(['name' => RoleEnum::ARTWORK_ADMIN->value]);
        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        return $user;
    }

}
