<?php

namespace Artwork\Modules\IndividualTimes\Policies;

use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\IndividualTimes\Support\OwnIndividualTimeAccess;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IndividualTimePolicy
{
    use HandlesAuthorization;

    /**
     * Eigene Individualzeiten nach OwnIndividualTimeAccess (Recht "can enter own individual times" ab heute,
     * Verwaltungsrechte an jedem Tag); fremde nur mit "can manage availability".
     * (Artwork-Admins passieren bereits über Gate::before im AuthServiceProvider.)
     */
    private function manages(User $user, IndividualTime $individualTime): bool
    {
        if (
            $individualTime->timeable_type === User::class
            && (int) $individualTime->timeable_id === (int) $user->id
        ) {
            return OwnIndividualTimeAccess::mayEnterOwnTimesOn($user, [$individualTime->start_date]);
        }

        // Freelancer/ServiceProvider sind "Worker": Worker-Manager dürfen deren Individualzeiten
        // pflegen (das Frontend gated die Bearbeitung genau auf "can manage workers").
        if (
            $individualTime->timeable_type !== User::class
            && $user->canAny([PermissionEnum::MA_MANAGER->value, PermissionEnum::EXTERNAL_MANAGER->value])
        ) {
            return true;
        }

        return $user->can(PermissionEnum::AVAILABILITY_MANAGEMENT->value);
    }

    public function update(User $user, IndividualTime $individualTime): bool
    {
        return $this->manages($user, $individualTime);
    }

    public function delete(User $user, IndividualTime $individualTime): bool
    {
        return $this->manages($user, $individualTime);
    }
}
