<?php

namespace Artwork\Modules\Event\Policies;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        if (
            $user->can(PermissionEnum::EVENT_REQUEST->value) ||
            $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
            $user->can(PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value) ||
            $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value) ||
            $user->can(PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value)
        ) {
            return true;
        }

        // Fallback: check room-specific permissions (room admin or requestable-by)
        $roomId = request()->get('roomId');
        if ($roomId) {
            $room = Room::find($roomId);
            if ($room) {
                return $room->admins()->where('user_id', $user->id)->exists()
                    || $room->requestableBy()->where('user_id', $user->id)->exists();
            }
        }

        return false;
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can(PermissionEnum::PROJECT_MANAGEMENT->value) ||
            $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value) ||
            $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->exists() ||
            $event->creator?->id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->can(PermissionEnum::PROJECT_MANAGEMENT->value) ||
            $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value) ||
            ($event->is_planning && $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value)) ||
            $event->room?->users()
                ->wherePivot('is_admin', true)
                ->where('user_id', $user->id)
                ->exists() ||
            $event->creator?->id === $user->id;
    }
}
