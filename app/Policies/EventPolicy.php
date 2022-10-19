<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        //return $user->canAny([PermissionNameEnum::PROJECT_UPDATE, PermissionNameEnum::PROJECT_ADMIN]);
        return true;
    }

    public function update(User $user, Event $event)
    {
        if ($user->canAny([PermissionNameEnum::PROJECT_UPDATE, PermissionNameEnum::PROJECT_ADMIN])) {
            return true;
        }

        return $event->room?->room_admins->where('id', $user->id)->isNotEmpty() ?? false;
    }

    public function delete(User $user, Event $event)
    {
        if ($user->canAny([PermissionNameEnum::PROJECT_UPDATE, PermissionNameEnum::PROJECT_ADMIN])) {
            return true;
        }

        return $event->room?->room_admins->where('id', $user->id)->isNotEmpty() ?? false;
    }
}
