<?php

namespace Artwork\Modules\Budget\Policies;

use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Projektbezogene Datensätze (project_id gesetzt) hängen am Projekt-Sage-Recht, globale
 * (project_id null) am globalen Sage-Recht — auch beim Löschen/Wiederherstellen, nicht nur beim Lesen.
 */
class SageNotAssignedDataPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, SageNotAssignedData $sageNotAssignedData): bool
    {
        return $this->canHandle($user, $sageNotAssignedData);
    }

    public function getTrashed(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::VIEW_PROJECT_SAGE_DATA->value,
            PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value,
        ]);
    }

    public function restore(User $user, SageNotAssignedData $sageNotAssignedData): bool
    {
        return $this->canHandle($user, $sageNotAssignedData);
    }

    public function forceDelete(User $user, SageNotAssignedData $sageNotAssignedData): bool
    {
        return $this->canHandle($user, $sageNotAssignedData);
    }

    private function canHandle(User $user, SageNotAssignedData $sageNotAssignedData): bool
    {
        return $sageNotAssignedData->project_id !== null
            ? $user->can(PermissionEnum::VIEW_PROJECT_SAGE_DATA->value)
            : $user->can(PermissionEnum::VIEW_GLOBAL_SAGE_DATA->value);
    }
}
