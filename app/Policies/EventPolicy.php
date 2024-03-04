<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use App\Models\User;
use Artwork\Modules\Event\Models\Event;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->can([PermissionNameEnum::EVENT_REQUEST->value]);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can(PermissionNameEnum::PROJECT_MANAGEMENT->value) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->get() ||
            $event->creator?->id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->can(PermissionNameEnum::PROJECT_MANAGEMENT->value) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->get() ||
            $event->creator?->id === $user->id;
    }
}
