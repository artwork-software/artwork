<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);
    }
}
