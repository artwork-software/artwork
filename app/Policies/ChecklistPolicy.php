<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChecklistPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param Checklist $checklist
     * @return bool
     */
    public function view(User $user, Checklist $checklist): bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value) ||
            $checklist->departments->users->contains($user->id);
    }

    /**
     * @return bool
     */
    public function create(): bool
    {
        return true;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    /**
     * @param User $user
     * @param Checklist $checklist
     * @return void
     */
    public function restore(User $user, Checklist $checklist): void
    {
        //
    }

    /**
     * @param User $user
     * @param Checklist $checklist
     * @return void
     */
    public function forceDelete(User $user, Checklist $checklist): void
    {
        //
    }
}
