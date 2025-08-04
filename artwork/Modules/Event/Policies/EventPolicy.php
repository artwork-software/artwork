<?php

namespace Artwork\Modules\Event\Policies;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->can([PermissionEnum::EVENT_REQUEST->value]) ||
               $user->can([PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value]) ||
                $user->can([PermissionEnum::CAN_SEE_PLANNING_CALENDAR]) ||
                $user->can([PermissionEnum::CAN_EDIT_PLANNING_CALENDAR]);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can(PermissionEnum::PROJECT_MANAGEMENT->value) ||
            $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->get() ||
            $event->creator?->id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->can(PermissionEnum::PROJECT_MANAGEMENT->value) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->get() ||
            $event->creator?->id === $user->id;
    }
}
