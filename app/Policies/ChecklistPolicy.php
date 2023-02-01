<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\Checklist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChecklistPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checklist  $checklist
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Checklist $checklist)
    {
        return $checklist->departments->users->contains($user->id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checklist  $checklist
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Checklist $checklist)
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value)
            && $user->departments->intersect($checklist->departments)->isNotEmpty();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checklist  $checklist
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Checklist $checklist)
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value
            && $user->departments->intersect($checklist->departments)->isNotEmpty());
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checklist  $checklist
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Checklist $checklist)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checklist  $checklist
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Checklist $checklist)
    {
        //
    }
}
