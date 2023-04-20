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
        if ($user->canAny([PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value, PermissionNameEnum::PROJECT_MANAGEMENT->value])) {
            return true;
        }

        return $event->room?->users()->wherePivot('is_admin', true)->where('id', $user->id)->get() || $event->creator?->id === $user->id ?? false;
    }

    public function delete(User $user, Event $event)
    {
        if ($user->canAny([PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value, PermissionNameEnum::PROJECT_MANAGEMENT->value])) {
            return true;
        }
        return $event->room?->users()->wherePivot('is_admin', true)->where('id', $user->id)->get() || $event->creator?->id === $user->id ?? false;
    }
}
