<?php

namespace Artwork\Modules\Department\Policies;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public const ROOM_ADMIN_TEAM_LOCKED_MESSAGE =
        'Members of teams with room admin access can only be changed by people who may edit rooms.';


    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionEnum::PROJECT_MANAGEMENT->value);
    }


    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value) ||
            $user->can(PermissionEnum::PROJECT_MANAGEMENT->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value);
    }


    public function update(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::TEAM_UPDATE->value);
    }

    /**
     * Mitglieder eines Teams mit Raumadmin-Zugriff ändern gibt bzw. nimmt Raumadmin-Rechte. Teampflege allein
     * reicht dafür nicht (sonst könnte man sich selbst zum Raumadmin machen): es braucht das Recht, Räume zu
     * bearbeiten (Admins über Gate::before).
     */
    public function changeRoomAdminMembers(User $user, Department $department): bool
    {
        return !$department->administeredRooms()->exists()
            || $user->can(PermissionEnum::ROOM_UPDATE->value);
    }
}
