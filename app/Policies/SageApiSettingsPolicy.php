<?php

namespace App\Policies;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SageApiSettingsPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->can(PermissionNameEnum::SETTINGS_UPDATE->value);
    }

    public function updateInterfaceSettings(User $user): bool
    {
        return $user->can(PermissionNameEnum::SETTINGS_UPDATE->value);
    }
}
