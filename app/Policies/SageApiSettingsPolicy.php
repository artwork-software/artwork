<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SageApiSettingsPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->can('change tool settings');
    }

    public function updateInterfaceSettings(User $user): bool
    {
        return $user->can('change tool settings');
    }
}
