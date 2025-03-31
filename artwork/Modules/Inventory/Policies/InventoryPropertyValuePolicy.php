<?php

namespace Artwork\Modules\Inventory\Policies;

use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Artwork\Modules\User\Models\User;

class InventoryPropertyValuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryPropertyValue $inventoryPropertyValue): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventoryPropertyValue $inventoryPropertyValue): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryPropertyValue $inventoryPropertyValue): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InventoryPropertyValue $inventoryPropertyValue): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InventoryPropertyValue $inventoryPropertyValue): bool
    {
        //
    }
}
