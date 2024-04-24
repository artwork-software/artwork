<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

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
}
