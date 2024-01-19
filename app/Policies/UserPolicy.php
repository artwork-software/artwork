<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    use HandlesAuthorization;

    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value) ||
            $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value) ||
            Auth::user()->id === $user->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value) ||
            $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value) ||
            $user->id == $model->id;
    }

    public function updateWorkProfile(User $user): bool
    {
        return $user->can(PermissionNameEnum::MA_MANAGER->value);
    }

    public function updateTerms(User $user): bool
    {
        return $user->can(PermissionNameEnum::MA_MANAGER->value);
    }
}
