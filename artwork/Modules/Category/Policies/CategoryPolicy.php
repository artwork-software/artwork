<?php

namespace Artwork\Modules\Category\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);
    }
}
