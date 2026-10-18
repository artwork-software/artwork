<?php

namespace Tests\Concerns;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait ActsAsRole
{
    protected function actingAsAdmin(?User $user = null): User
    {
        $user = $this->adminUser($user);
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsRole(RoleEnum $role, ?User $user = null): User
    {
        $this->ensureRolesAndPermissionsSeeded();
        Role::findOrCreate($role->value, 'web');
        $user = $user ?? User::factory()->create();
        $user->assignRole($role->value);
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsUserWith(string|array $permissions, ?User $user = null): User
    {
        $user = $this->userWithPermissions($permissions, $user);
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsApiUserWith(string|array $permissions = [], ?User $user = null): User
    {
        $user = $this->userWithPermissions($permissions, $user);
        Passport::actingAs($user, ['app']);

        return $user;
    }

    private function userWithPermissions(string|array $permissions, ?User $user): User
    {
        $this->ensureRolesAndPermissionsSeeded();
        $user = $user ?? User::factory()->create();

        foreach ((array) $permissions as $permission) {
            $name = $permission instanceof PermissionEnum ? $permission->value : $permission;
            Permission::findOrCreate($name, 'web');
            $user->givePermissionTo($name);
        }

        return $user;
    }

    private function ensureRolesAndPermissionsSeeded(): void
    {
        if (!Role::query()->where('name', RoleEnum::ARTWORK_ADMIN->value)->exists()) {
            $this->seed(RolesAndPermissionsSeeder::class);
        }
    }
}
