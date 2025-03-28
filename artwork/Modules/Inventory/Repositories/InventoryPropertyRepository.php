<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;

class InventoryPropertyRepository
{
    public function all()
    {
        return InventoryArticleProperties::all();
    }

    public function filterable()
    {
        return InventoryArticleProperties::filterable()->get();
    }

    public function find(int $id)
    {
        return InventoryArticleProperties::find($id);
    }
}