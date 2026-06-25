<?php

namespace Artwork\Modules\Vacation\Policies;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Illuminate\Auth\Access\HandlesAuthorization;

class VacationPolicy
{
    use HandlesAuthorization;

    /**
     * Eigene Urlaube darf jeder verwalten; fremde nur mit "can manage availability".
     * (Artwork-Admins passieren bereits über Gate::before im AuthServiceProvider.)
     */
    private function manages(User $user, Vacation $vacation): bool
    {
        if (
            $vacation->vacationer_type === User::class
            && (int) $vacation->vacationer_id === (int) $user->id
        ) {
            return true;
        }

        return $user->can(PermissionEnum::AVAILABILITY_MANAGEMENT->value);
    }

    public function update(User $user, Vacation $vacation): bool
    {
        return $this->manages($user, $vacation);
    }

    public function delete(User $user, Vacation $vacation): bool
    {
        return $this->manages($user, $vacation);
    }
}
