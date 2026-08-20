<?php

namespace Artwork\Modules\System\ApiManagement\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TokenPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermissionEnum::SETTINGS_UPDATE->value);
    }
}
