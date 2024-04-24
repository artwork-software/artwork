<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AreaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionNameEnum::ROOM_UPDATE->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermissionNameEnum::ROOM_UPDATE->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionNameEnum::ROOM_UPDATE->value);
    }


    public function update(User $user): bool
    {
        return $user->can(PermissionNameEnum::ROOM_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionNameEnum::ROOM_UPDATE->value);
    }
}
