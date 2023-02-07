<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\Area;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AreaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasRole(RoleNameEnum::ROOM_ADMIN->value);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Area  $area
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Area $area)
    {
        return $user->hasRole(RoleNameEnum::ROOM_ADMIN->value);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasRole(RoleNameEnum::ROOM_ADMIN->value);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Area  $area
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Area $area)
    {
        return $user->hasRole(RoleNameEnum::ROOM_ADMIN->value);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Area  $area
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Area $area)
    {
        return $user->hasRole(RoleNameEnum::ROOM_ADMIN->value);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Area  $area
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Area $area)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Area  $area
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Area $area)
    {
        //
    }
}
