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

    /**
     * Determine whether the user can view any models.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionNameEnum::PROJECT_MANAGEMENT->value);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionNameEnum::PROJECT_MANAGEMENT->value);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionNameEnum::DEPARTMENT_UPDATE->value);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionNameEnum::DEPARTMENT_UPDATE->value);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->can(PermissionNameEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionNameEnum::DEPARTMENT_UPDATE->value);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param Department $department
     * @return void
     */
    public function restore(User $user, Department $department)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param Department $department
     * @return void
     */
    public function forceDelete(User $user, Department $department)
    {
        //
    }
}
