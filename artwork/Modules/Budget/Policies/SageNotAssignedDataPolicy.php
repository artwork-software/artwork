<?php

namespace Artwork\Modules\Budget\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SageNotAssignedDataPolicy
{
    use HandlesAuthorization;

    public function delete(User $user): bool
    {
        return $user->can(PermissionNameEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value);
    }

    public function getTrashed(User $user): bool
    {
        return $user->can(PermissionNameEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value);
    }

    public function restore(User $user): bool
    {
        return $user->can(PermissionNameEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value);
    }

    public function forceDelete(User $user): bool
    {
        return $user->can(PermissionNameEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value);
    }
}
