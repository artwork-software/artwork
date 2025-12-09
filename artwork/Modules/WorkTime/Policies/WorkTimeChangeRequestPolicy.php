<?php

namespace Artwork\Modules\WorkTime\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
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
        // Allow admins and shift planners to update (approve/decline) requests
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(PermissionEnum::SHIFT_PLANNER->value)) {
            // Check if user is assigned as craft shift planner OR craft is assignable by all
            $craft = $workTimeChangeRequest->craft;

            if ($craft->assignable_by_all) {
                return true;
            }

            return $craft->craftShiftPlaner()->where('user_id', $user->id)->exists();
        }

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
