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

    protected function syncPropertiesWithArticles($categoryOrSubCategoryModel, $newProperties): void
    {
        $newPropertyIdsWithValues = collect($newProperties)->keyBy('id');
        $newPropertyIds = $newPropertyIdsWithValues->keys()->toArray();
        $currentPropertyIds = $categoryOrSubCategoryModel->properties()->pluck('inventory_article_property_id')->toArray();

        $toAdd = array_diff($newPropertyIds, $currentPropertyIds);
        $toRemove = array_diff($currentPropertyIds, $newPropertyIds);
        $toUpdate = array_intersect($newPropertyIds, $currentPropertyIds);

        if (!empty($toRemove)) {
            $categoryOrSubCategoryModel->properties()->detach($toRemove);

            foreach ($categoryOrSubCategoryModel->articles as $article) {
                $article->properties()->detach($toRemove);
            }
        }

        foreach ($toAdd as $propertyId) {
            $defaultValue = $newPropertyIdsWithValues[$propertyId]['defaultValue'] ?? '';

            $categoryOrSubCategoryModel->properties()->attach($propertyId, ['value' => $defaultValue]);

            foreach ($categoryOrSubCategoryModel->articles as $article) {
                $article->properties()->syncWithoutDetaching([
                    $propertyId => ['value' => $defaultValue]
                ]);
            }
        }

        // Update existing properties with new default values
        foreach ($toUpdate as $propertyId) {
            $defaultValue = $newPropertyIdsWithValues[$propertyId]['defaultValue'] ?? '';

            // Update the pivot value for the existing property
            $categoryOrSubCategoryModel->properties()->updateExistingPivot($propertyId, ['value' => $defaultValue]);

            // Update articles that don't have a custom value set
            foreach ($categoryOrSubCategoryModel->articles as $article) {
                // Only update if the article has this property and the value is empty or matches the old default
                $articleProperty = $article->properties()->where('inventory_article_property_id', $propertyId)->first();
                if ($articleProperty && empty($articleProperty->pivot->value)) {
                    $article->properties()->updateExistingPivot($propertyId, ['value' => $defaultValue]);
                }
            }
        }
    }

}
