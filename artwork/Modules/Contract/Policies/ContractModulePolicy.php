<?php

namespace Artwork\Modules\Contract\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Vertragsbausteine: Einsehen/Herunterladen mit dem Lese-Recht ODER dem Verwaltungsrecht
 * (Superset), Hochladen/Löschen nur mit dem Verwaltungsrecht.
 */
class ContractModulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::CONTRACT_SEE_DOWNLOAD->value,
            PermissionEnum::CONTRACT_EDIT_UPLOAD->value,
        ]);
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CONTRACT_EDIT_UPLOAD->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::CONTRACT_EDIT_UPLOAD->value);
    }
}
