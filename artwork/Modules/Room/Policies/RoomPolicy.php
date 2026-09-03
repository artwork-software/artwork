<?php

namespace Artwork\Modules\Room\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * "Raumverwaltung" (create, delete and update rooms) gilt hausweit; ein Raum-Admin
 * (room_user.is_admin) darf zusätzlich seinen eigenen Raum bearbeiten. Admins via Gate::before.
 */
class RoomPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ROOM_UPDATE->value);
    }

    public function view(User $user, Room $room): bool
    {
        return $user->can(PermissionEnum::ROOM_UPDATE->value) || $this->isRoomAdmin($user, $room);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ROOM_UPDATE->value);
    }

    public function update(User $user, Room $room): bool
    {
        return $user->can(PermissionEnum::ROOM_UPDATE->value) || $this->isRoomAdmin($user, $room);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::ROOM_UPDATE->value);
    }

    public function restore(User $user): bool
    {
        return $user->can(PermissionEnum::ROOM_UPDATE->value);
    }

    public function forceDelete(User $user): bool
    {
        return $user->can(PermissionEnum::ROOM_UPDATE->value);
    }

    private function isRoomAdmin(User $user, Room $room): bool
    {
        return $room->users()
            ->where('users.id', $user->id)
            ->wherePivot('is_admin', true)
            ->exists();
    }
}
