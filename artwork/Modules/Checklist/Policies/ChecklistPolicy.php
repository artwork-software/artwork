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
            $checklist->departments->users->contains($user->id) || $checklist->projects?->users->contains($user->id) ||
            ($user->can(PermissionEnum::CHECKLIST_USE_PERMISSION->value) && $checklist->user_id === $user->id) ||
            $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, Checklist $checklist): bool
    {
        // todo: hier anpassen, wenn die Berechtigungen für die Checkliste festgelegt sind
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value) ||
            $checklist->project?->users->contains($user->id) ||
            $checklist->users->contains($user->id) ||
            // if user is in any task in checklist
            $checklist->tasks->each(function ($task) use ($user) {
                return $task->task_users->contains($user->id);
            }) || ($user->can(PermissionEnum::CHECKLIST_USE_PERMISSION->value) && $checklist->user_id === $user->id) ||
            $user->can(PermissionEnum::CHECKLIST_EDIT_PERMISSION->value);
    }

    public function delete(User $user, Checklist $checklist): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value) ||
            ($user->can(PermissionEnum::CHECKLIST_USE_PERMISSION->value) && $checklist->user_id === $user->id) ||
            $user->can(PermissionEnum::CHECKLIST_EDIT_PERMISSION->value);
    }
}
