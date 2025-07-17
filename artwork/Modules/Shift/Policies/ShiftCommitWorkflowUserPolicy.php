<?php

namespace Artwork\Modules\Shift\Policies;

use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\User\Models\User;

class ShiftCommitWorkflowUserPolicy
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
    public function view(User $user, ShiftCommitWorkflowUser $shiftCommitWorkflowUser): bool
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
    public function update(User $user, ShiftCommitWorkflowUser $shiftCommitWorkflowUser): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShiftCommitWorkflowUser $shiftCommitWorkflowUser): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ShiftCommitWorkflowUser $shiftCommitWorkflowUser): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ShiftCommitWorkflowUser $shiftCommitWorkflowUser): bool
    {
        return false;
    }
}
