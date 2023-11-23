<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param User $user
     * @return Response|bool
     */
    public function viewAny(User $user): Response|bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value) || $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value) ||
            $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value) || $user->id == $model->id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @return void
     */
    public function restore(User $user): void
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @return void
     */
    public function forceDelete(User $user): void
    {
        //
    }
}
