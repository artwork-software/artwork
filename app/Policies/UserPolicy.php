<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(): bool
    {
        return true;
    }


    public function view(): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value) || $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value);
    }


    public function delete(User $user, User $model): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value) ||
            $user->hasRole(RoleNameEnum::ARTWORK_ADMIN->value) ||
            $user->id == $model->id;
    }

    public function restore(): void
    {
    }

    public function forceDelete(): void
    {
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
