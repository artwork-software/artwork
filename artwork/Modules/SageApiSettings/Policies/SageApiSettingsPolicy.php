<?php

namespace Artwork\Modules\SageApiSettings\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SageApiSettingsPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }

    public function updateInterfaceSettings(User $user): bool
    {
        return $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }
}
