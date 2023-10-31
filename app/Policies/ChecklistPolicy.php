<?php

/**********
 * @todo These permission checks dont seem right to me
 **********/


namespace App\Policies;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ChecklistPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Checklist $checklist): Response|bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value) || $checklist->departments->users->contains($user->id);
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Checklist $checklist)
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);
           // && $user->departments->intersect($checklist->departments)->isNotEmpty();
    }

    public function delete(User $user, Checklist $checklist)
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value)
            && $user->departments->intersect($checklist->departments)->isNotEmpty();
    }

    public function restore(User $user, Checklist $checklist)
    {
        //
    }

    public function forceDelete(User $user, Checklist $checklist)
    {
        //
    }
}
