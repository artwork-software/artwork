<?php

namespace App\Policies;

use Artwork\Modules\User\Models\UserWorkTimePattern;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\Response;

class UserWorkTimePatternPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserWorkTimePattern $userWorkTimePattern): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserWorkTimePattern $userWorkTimePattern): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserWorkTimePattern $userWorkTimePattern): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserWorkTimePattern $userWorkTimePattern): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserWorkTimePattern $userWorkTimePattern): bool
    {
        //
    }
}
