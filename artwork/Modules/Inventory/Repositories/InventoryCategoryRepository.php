<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Modules\Inventory\Models\InventoryCategory;

class InventoryCategoryRepository
{
    public function allWithRelations()
    {
        return InventoryCategory::with(['subcategories.properties', 'properties'])->get();
    }

    public function paginateWithRelations(int $perPage = 50)
    {
        return InventoryCategory::with(['properties', 'subcategories', 'subcategories.properties'])->paginate($perPage);
    }

    public function create(array $data)
    {
        return InventoryCategory::create($data);
    }

    public function update(InventoryCategory $category, array $data)
    {
        $category->update($data);
        return $category;
    }
}