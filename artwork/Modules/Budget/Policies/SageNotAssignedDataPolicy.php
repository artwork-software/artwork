<?php

namespace Artwork\Modules\Budget\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SageNotAssignedDataPolicy
{
    use HandlesAuthorization;

    public function delete(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::VIEW_PROJECT_SAGE_DATA->value,
            PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value,
        ]);
    }

    public function getTrashed(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::VIEW_PROJECT_SAGE_DATA->value,
            PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value,
        ]);
    }

    public function restore(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::VIEW_PROJECT_SAGE_DATA->value,
            PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value,
        ]);
    }

    public function forceDelete(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::VIEW_PROJECT_SAGE_DATA->value,
            PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value,
        ]);
    }
}
