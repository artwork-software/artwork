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
        $this->syncPropertiesWithArticles($category, $properties);

        foreach ($subcategories as $subcategory) {
            $subCategory = $category->subcategories()->create($subcategory);
            $this->syncPropertiesWithArticles($subCategory, $subcategory['properties']);
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
        $this->syncPropertiesWithArticles($category, $properties);

        foreach ($subcategories as $subcategory) {
            $subcategory['inventory_category_id'] = $category->id;

            $subCategoryUpdated = $category->subcategories()->updateOrCreate(
                ['id' => $subcategory['id']],
                $subcategory
            );
            $this->syncPropertiesWithArticles($subCategoryUpdated, $subcategory['properties']);
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

    protected function syncPropertiesWithArticles($model, $newProperties): void
    {
        $newPropertyIdsWithValues = collect($newProperties)->keyBy('id');
        $newPropertyIds = $newPropertyIdsWithValues->keys()->toArray();
        $currentPropertyIds = $model->properties()->pluck('inventory_article_property_id')->toArray();

        $toAdd = array_diff($newPropertyIds, $currentPropertyIds);
        $toRemove = array_diff($currentPropertyIds, $newPropertyIds);

        if (!empty($toRemove)) {
            $model->properties()->detach($toRemove);

            foreach ($model->articles as $article) {
                $article->properties()->detach($toRemove);
            }
        }

        foreach ($toAdd as $propertyId) {
            $defaultValue = $newPropertyIdsWithValues[$propertyId]['defaultValue'] ?? '';

            $model->properties()->attach($propertyId, ['value' => $defaultValue]);

            foreach ($model->articles as $article) {
                $article->properties()->syncWithoutDetaching([
                    $propertyId => ['value' => $defaultValue]
                ]);
            }
        }
    }

}