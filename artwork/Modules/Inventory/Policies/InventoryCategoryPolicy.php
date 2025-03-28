<?php

namespace Artwork\Modules\Inventory\Policies;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\User\Models\User;

class InventoryCategoryPolicy
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
    public function view(User $user, InventoryCategory $inventoryCategory): bool
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
    public function update(User $user, InventoryCategory $inventoryCategory): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryCategory $inventoryCategory): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InventoryCategory $inventoryCategory): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InventoryCategory $inventoryCategory): bool
    {
        //
    }
}
