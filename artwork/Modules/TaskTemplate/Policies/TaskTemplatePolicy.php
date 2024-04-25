<?php

namespace Artwork\Modules\TaskTemplate\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }
}
