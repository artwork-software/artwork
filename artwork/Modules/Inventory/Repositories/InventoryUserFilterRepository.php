<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Modules\Inventory\Models\InventoryUserFilter;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Inventory\Interfaces\InventoryUserFilterInterface;

class InventoryUserFilterRepository implements InventoryUserFilterInterface
{
    /**
     * Holt die Filter-Einstellungen für einen User.
     */
    public function getByUser(User $user): ?InventoryUserFilter
    {
        return InventoryUserFilter::where('user_id', $user->id)->first();
    }

    /**
     * Speichert oder aktualisiert die Filter-Einstellungen für einen User.
     */
    public function saveForUser(User $user, array $data): InventoryUserFilter
    {
        return InventoryUserFilter::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );
    }
}
