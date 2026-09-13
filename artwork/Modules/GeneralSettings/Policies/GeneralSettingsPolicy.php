<?php

namespace Artwork\Modules\GeneralSettings\Policies;

use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GeneralSettingsPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->can('change tool settings');
    }

    public function updateImages(User $user): bool
    {
        return $user->can('change tool settings');
    }

    public function updateEmailSettings(User $user): bool
    {
        return $user->can('change tool settings');
    }

    public function updateBudgetAccountManagementGlobal(User $user): bool
    {
        return $user->can('can manage global project budgets');
    }
}
