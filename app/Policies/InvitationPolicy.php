<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('invite users');
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionNameEnum::USER_UPDATE->value);
    }

    public function restore(): void
    {
    }

    public function forceDelete(): void
    {
    }
}
