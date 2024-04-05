<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contract;
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
        return $contract->accessingUsers->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, contract $contract): bool
    {
        return $contract->accessingUsers->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $contract->accessingUsers->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }
}
