<?php

namespace App\Policies;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\Response;

class AccommodationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow viewing if the user is authenticated
        return $user->exists;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Accommodation $accommodation): bool
    {
        return $user->exists && $accommodation->exists;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->exists;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Accommodation $accommodation): bool
    {
        return $user->exists && $accommodation->exists;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Accommodation $accommodation): bool
    {
        return $user->exists && $accommodation->exists;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Accommodation $accommodation): bool
    {
        return $user->exists && $accommodation->exists;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Accommodation $accommodation): bool
    {
        return $user->exists && $accommodation->exists;
    }
}
