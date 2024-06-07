<?php

namespace Artwork\Modules\Checklist\Policies;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChecklistPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Checklist $checklist): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value) ||
            $checklist->departments->users->contains($user->id) || $checklist->projects->users->contains($user->id);
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, Checklist $checklist): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value) ||
            $checklist->project->users->contains($user->id);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }
}
