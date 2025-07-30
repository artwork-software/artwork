<?php

namespace Artwork\Modules\User\Policies;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilterTemplate;

class UserFilterTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserFilterTemplate $userFilterTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserFilterTemplate $userFilterTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserFilterTemplate $userFilterTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserFilterTemplate $userFilterTemplate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserFilterTemplate $userFilterTemplate): bool
    {
        return false;
    }
}
