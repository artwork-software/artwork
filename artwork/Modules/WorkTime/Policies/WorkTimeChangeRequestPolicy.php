<?php

namespace Artwork\Modules\WorkTime\Policies;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;

class WorkTimeChangeRequestPolicy
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
    public function view(User $user, WorkTimeChangeRequest $workTimeChangeRequest): bool
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
    public function update(User $user, WorkTimeChangeRequest $workTimeChangeRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkTimeChangeRequest $workTimeChangeRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkTimeChangeRequest $workTimeChangeRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkTimeChangeRequest $workTimeChangeRequest): bool
    {
        return false;
    }
}
