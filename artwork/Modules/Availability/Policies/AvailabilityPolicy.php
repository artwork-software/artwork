<?php

namespace Artwork\Modules\Availability\Policies;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AvailabilityPolicy
{
    use HandlesAuthorization;

    /**
     * Eigene Verfügbarkeiten darf jeder verwalten; fremde nur mit "can manage availability".
     * (Artwork-Admins passieren bereits über Gate::before im AuthServiceProvider.)
     */
    private function manages(User $user, Availability $availability): bool
    {
        if (
            $availability->available_type === User::class
            && (int) $availability->available_id === (int) $user->id
        ) {
            return true;
        }

        return $user->can(PermissionEnum::AVAILABILITY_MANAGEMENT->value);
    }

    public function update(User $user, Availability $availability): bool
    {
        return $this->manages($user, $availability);
    }

    public function delete(User $user, Availability $availability): bool
    {
        return $this->manages($user, $availability);
    }
}
