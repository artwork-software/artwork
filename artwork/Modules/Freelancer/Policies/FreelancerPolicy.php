<?php

namespace Artwork\Modules\Freelancer\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FreelancerPolicy
{
    use HandlesAuthorization;

    public function updateWorkProfile(User $user): bool
    {
        return $user->can(PermissionEnum::MA_MANAGER->value);
    }

    public function updateTerms(User $user): bool
    {
        return $user->can(PermissionEnum::MA_MANAGER->value);
    }
}
