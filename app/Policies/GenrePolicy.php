<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GenrePolicy
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
        return $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Genre  $genre
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Genre $genre)
    {
        return $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Genre  $genre
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Genre $genre)
    {
        return $user->can(PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Genre  $genre
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Genre $genre)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Genre  $genre
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Genre $genre)
    {
        //
    }
}
