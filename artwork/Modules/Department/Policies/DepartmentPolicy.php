<?php

namespace Artwork\Modules\Department\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionEnum::PROJECT_MANAGEMENT->value);
    }


    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionEnum::PROJECT_MANAGEMENT->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value);
    }


    public function update(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value);
    }
}
