<?php

namespace App\Policies;

use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\User\Models\User;

class ExternalIssueFilePolicy
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
    public function view(User $user, ExternalIssueFile $externalIssueFile): bool
    {
        // Allow viewing if the user is authenticated and the external issue file exists
        return $user->exists && $externalIssueFile->exists;
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
    public function update(User $user, ExternalIssueFile $externalIssueFile): bool
    {
        // Allow update if the user is authenticated and the external issue file exists
        return $user->exists && $externalIssueFile->exists;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExternalIssueFile $externalIssueFile): bool
    {
        // Allow deletion if the user is authenticated and the external issue file exists
        return $user->exists && $externalIssueFile->exists;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExternalIssueFile $externalIssueFile): bool
    {
        // Allow restoration if the user is authenticated and the external issue file exists
        return $user->exists && $externalIssueFile->exists;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExternalIssueFile $externalIssueFile): bool
    {
        // Allow permanent deletion if the user is authenticated and the external issue file exists
        return $user->exists && $externalIssueFile->exists;
    }
}
