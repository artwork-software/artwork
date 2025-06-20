<?php

namespace App\Policies;

use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\User\Models\User;

class DisclosureComponentsPolicy
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
    public function view(User $user, DisclosureComponents $disclosureComponents): bool
    {
        // Allow viewing if the user is authenticated and the disclosure component exists
        return $user->exists && $disclosureComponents->exists;
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
    public function update(User $user, DisclosureComponents $disclosureComponents): bool
    {
        // Allow update if the user is authenticated and the disclosure component exists
        return $user->exists && $disclosureComponents->exists;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DisclosureComponents $disclosureComponents): bool
    {
        // Allow deletion if the user is authenticated and the disclosure component exists
        return $user->exists && $disclosureComponents->exists;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DisclosureComponents $disclosureComponents): bool
    {
        // Allow restoration if the user is authenticated and the disclosure component exists
        return $user->exists && $disclosureComponents->exists;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DisclosureComponents $disclosureComponents): bool
    {
        // Allow permanent deletion if the user is authenticated and the disclosure component exists
        return $user->exists && $disclosureComponents->exists;
    }
}
