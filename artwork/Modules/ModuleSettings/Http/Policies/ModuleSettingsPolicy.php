<?php

namespace Artwork\Modules\ModuleSettings\Http\Policies;

use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ModuleSettingsPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('change tool settings');
    }

    public function update(User $user): bool
    {
        return $user->can('change tool settings');
    }
}
