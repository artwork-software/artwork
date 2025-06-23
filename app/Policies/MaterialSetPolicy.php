<?php

namespace App\Policies;

use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\Response;

class MaterialSetPolicy
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
    public function view(User $user, MaterialSet $materialSet): bool
    {
        // Allow viewing if the user is authenticated and the material set exists
        return $user->exists && $materialSet->exists;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Allow creation if the user is authenticated
        return $user->exists;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MaterialSet $materialSet): bool
    {
        // Allow update if the user is authenticated and the material set exists
        return $user->exists && $materialSet->exists;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaterialSet $materialSet): bool
    {
        // Allow deletion if the user is authenticated and the material set exists
        return $user->exists && $materialSet->exists;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaterialSet $materialSet): bool
    {
        // Allow restoration if the user is authenticated and the material set exists
        return $user->exists && $materialSet->exists;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaterialSet $materialSet): bool
    {
        // Allow permanent deletion if the user is authenticated and the material set exists
        return $user->exists && $materialSet->exists;
    }
}
