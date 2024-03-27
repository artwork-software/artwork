<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\User;
use Artwork\Modules\Department\Models\Department;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DepartmentPolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionNameEnum::PROJECT_MANAGEMENT->value);
    }


    public function view(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionNameEnum::PROJECT_MANAGEMENT->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value);
    }


    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value);
    }


    public function restore(): void
    {
        //
    }

    public function forceDelete(): void
    {
        //
    }
}
