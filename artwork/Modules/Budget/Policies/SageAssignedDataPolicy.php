<?php

namespace Artwork\Modules\Budget\Policies;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SageAssignedDataPolicy
{
    use HandlesAuthorization;

    public function delete(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::VIEW_PROJECT_SAGE_DATA->value,
            PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value,
            PermissionEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value, // Legacy support
        ]);
    }
}
