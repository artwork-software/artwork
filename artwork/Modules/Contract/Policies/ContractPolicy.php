<?php

namespace Artwork\Modules\Contract\Policies;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Contract $contract): bool
    {
        return $this->hasAccess($user, $contract);
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, contract $contract): bool
    {
        return $this->hasAccess($user, $contract);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $this->hasAccess($user, $contract);
    }

    private function hasAccess(User $user, Contract $contract): bool
    {
        // Ersteller behält Zugriff (die Vertragsliste zeigt eigene Verträge via creator_id;
        // ohne diese Prüfung bekäme der Ersteller einen 403 auf seinen eigenen Vertrag).
        if ((int) $contract->creator_id === (int) $user->id) {
            return true;
        }

        // Check if user has direct access
        if ($contract->accessingUsers->contains($user->id)) {
            return true;
        }

        // Check if user is project manager
        if ($contract->project && $contract->project->managerUsers->contains($user->id)) {
            return true;
        }

        // Check if user belongs to a department with access
        $userDepartmentIds = $user->departments->pluck('id')->toArray();
        $contractDepartmentIds = $contract->accessingDepartments->pluck('id')->toArray();

        if (!empty(array_intersect($userDepartmentIds, $contractDepartmentIds))) {
            return true;
        }

        return false;
    }
}
