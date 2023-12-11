<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contract;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return bool
     */
    public function viewAny(): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param Contract $contract
     * @return bool
     */
    public function view(User $user, Contract $contract): bool
    {
        return $contract->accessing_users->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return bool
     */
    public function create(): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param contract $contract
     * @return bool
     */
    public function update(User $user, contract $contract): bool
    {
        return $contract->accessing_users->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param Contract $contract
     * @return bool
     */
    public function delete(User $user, Contract $contract): bool
    {
        return $contract->accessing_users->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return void
     */
    public function restore(): void
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return bool
     */
    public function forceDelete(): bool
    {
        //
    }
}
