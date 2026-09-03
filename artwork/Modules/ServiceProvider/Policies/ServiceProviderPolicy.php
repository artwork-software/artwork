<?php

namespace Artwork\Modules\ServiceProvider\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceProviderPolicy
{
    use HandlesAuthorization;

    /** Externe pflegen: "Personalverwaltung" oder das eigenständige Recht "Externe verwalten". */
    public static function canManageExternals(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::MA_MANAGER->value,
            PermissionEnum::EXTERNAL_MANAGER->value,
        ]);
    }

    public function updateWorkProfile(User $user): bool
    {
        return self::canManageExternals($user);
    }

    public function updateTerms(User $user): bool
    {
        return self::canManageExternals($user);
    }
}
