<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Repositories\InventoryCategoryRepository;

class InventoryCategoryService
{
    public function __construct(protected InventoryCategoryRepository $repository) {

    }

    public function createWithRelations(array $data, $properties, $subcategories)
    {
        $category = $this->repository->create($data);

        foreach ($properties as $property) {
            $category->properties()->attach($property['id'], ['value' => $property['defaultValue']]);
        }

        foreach ($subcategories as $subcategory) {
            $subCategory = $category->subcategories()->create($subcategory);
            foreach ($subcategory['properties'] as $property) {
                $subCategory->properties()->attach($property['id'], ['value' => $property['defaultValue']]);
            }
        }

        return $category;
    }

    public function updateWithRelations(
        InventoryCategory $category,
        array $data,
        $properties,
        $subcategories
    ): ?InventoryCategory {
        $this->repository->update($category, $data);
        $category->properties()->detach();

        foreach ($properties as $property) {
            $category->properties()->attach($property['id'], ['value' => $property['defaultValue']]);
        }

        foreach ($subcategories as $subcategory) {
            $subcategory['inventory_category_id'] = $category->id;

            $subCategoryUpdated = $category->subcategories()->updateOrCreate(
                ['id' => $subcategory['id']],
                $subcategory
            );

            $subCategoryUpdated->properties()->sync([]);
            foreach ($subcategory['properties'] as $property) {
                $subCategoryUpdated->properties()->attach($property['id'], ['value' => $property['defaultValue']]);
            }
        }

        return $category->fresh();
    }

    public function getAllWithRelations(): \Illuminate\Database\Eloquent\Collection|array
    {
        return $this->repository->allWithRelations();
    }

    public function paginateWithRelations(int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->paginateWithRelations($perPage);
    }
}