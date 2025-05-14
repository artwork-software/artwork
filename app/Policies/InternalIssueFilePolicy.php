<?php

namespace App\Policies;

use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\User\Models\User;

class InternalIssueFilePolicy
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
    public function view(User $user, InternalIssueFile $internalIssueFile): bool
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
    public function update(User $user, InternalIssueFile $internalIssueFile): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InternalIssueFile $internalIssueFile): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InternalIssueFile $internalIssueFile): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InternalIssueFile $internalIssueFile): bool
    {
        //
    }
}
