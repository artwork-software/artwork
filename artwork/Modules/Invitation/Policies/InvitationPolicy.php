<?php

namespace Artwork\Modules\Invitation\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::MA_MANAGER->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::MA_MANAGER->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionEnum::MA_MANAGER->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::MA_MANAGER->value);
    }
}
