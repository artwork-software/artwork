<?php

namespace Artwork\Modules\ExternalAccess\Policies;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExternalAccessPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ExternalAccess $access): bool
    {
        return $user->can(PermissionEnum::CRM_VIEW->value);
    }

    /**
     * Managing (extend/revoke/scope changes) is allowed for CRM managers or the inviter.
     */
    public function manage(User $user, ExternalAccess $access): bool
    {
        if ($user->can(PermissionEnum::CRM_MANAGER->value)) {
            return true;
        }

        return $access->invited_by_user_id === $user->id;
    }

    /**
     * Re-linking to another CRM contact is an administrative action — managers only.
     */
    public function relink(User $user, ExternalAccess $access): bool
    {
        return $user->can(PermissionEnum::CRM_MANAGER->value);
    }
}
