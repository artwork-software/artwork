<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;

class InventoryPropertyRepository
{
    public function all()
    {
        // Ref 1.41: globale Eigenschafts-Reihenfolge anwenden.
        return InventoryArticleProperties::ordered()->get();
    }

    public function filterable()
    {
        return InventoryArticleProperties::filterable()->ordered()->get();
    }

    public function find(int $id)
    {
        return InventoryArticleProperties::find($id);
    }
}