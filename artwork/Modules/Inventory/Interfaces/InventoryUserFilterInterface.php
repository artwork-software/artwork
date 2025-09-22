<?php

namespace Artwork\Modules\Inventory\Interfaces;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\Inventory\Models\InventoryUserFilter;

interface InventoryUserFilterInterface
{
    public function getByUser(User $user): ?InventoryUserFilter;
    public function saveForUser(User $user, array $data): InventoryUserFilter;
}
