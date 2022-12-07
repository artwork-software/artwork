<?php

namespace App\Policies;

use App\Models\User;
use App\Models\contract;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\contract  $contract
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, contract $contract)
    {
        return $contract->accessing_users->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\contract  $contract
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, contract $contract)
    {
        return $contract->accessing_users->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\contract  $contract
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, contract $contract)
    {
        return $contract->accessing_users->contains($user->id) || $contract->project->managerUsers->contains($user->id);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\contract  $contract
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, contract $contract)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\contract  $contract
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, contract $contract)
    {
        //
    }
}
