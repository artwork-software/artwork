<?php

namespace Tests;

use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

trait CreateAdminUser
{
    public function adminUser(User $user = null): User
    {
        // Ensure roles and permissions exist before assigning them
        if (!Role::where('name', RoleEnum::ARTWORK_ADMIN->value)->exists()) {
            $this->artisan('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);
        }

        $user = $user ?? User::factory()->create();
        $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);
        if (!$user->calendar_settings) {
            UserCalendarSettings::factory()->create(['user_id' => $user->id]);
        }

        return $user;
    }
}
