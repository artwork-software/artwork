<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GeneralSettingsPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->can('change tool settings');
    }

    public function updateToolImages(User $user): bool
    {
        return $user->can('change tool settings');
    }

    public function updateToolEmailSettings(User $user): bool
    {
        return $user->can('change tool settings');
    }
}
