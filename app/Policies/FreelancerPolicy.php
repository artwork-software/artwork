<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FreelancerPolicy
{
    use HandlesAuthorization;

    public function updateWorkProfile(User $user): bool
    {
        return $user->can(PermissionNameEnum::MA_MANAGER->value);
    }

    public function updateTerms(User $user): bool
    {
        return $user->can(PermissionNameEnum::MA_MANAGER->value);
    }
}
