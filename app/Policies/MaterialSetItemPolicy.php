<?php

namespace App\Policies;

use Artwork\Modules\MaterialSet\Models\MaterialSetItem;
use Artwork\Modules\User\Models\User;

class MaterialSetItemPolicy
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
    public function view(User $user, MaterialSetItem $materialSetItem): bool
    {
        // Allow viewing if the user is authenticated and the material set item exists
        return $user->exists && $materialSetItem->exists;
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
    public function update(User $user, MaterialSetItem $materialSetItem): bool
    {
        // Allow update if the user is authenticated and the material set item exists
        return $user->exists && $materialSetItem->exists;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaterialSetItem $materialSetItem): bool
    {
        // Allow deletion if the user is authenticated and the material set item exists
        return $user->exists && $materialSetItem->exists;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaterialSetItem $materialSetItem): bool
    {
        // Allow restoration if the user is authenticated and the material set item exists
        return $user->exists && $materialSetItem->exists;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaterialSetItem $materialSetItem): bool
    {
        // Allow permanent deletion if the user is authenticated and the material set item exists
        return $user->exists && $materialSetItem->exists;
    }
}
