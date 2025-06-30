<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Repositories\InventoryCategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class InventoryCategoryService
{
    public function __construct(
        protected InventoryCategoryRepository $categoryRepository
    ) {}

    /**
     * Get all categories with optimized relations
     */
    public function getAllWithRelations(): Collection
    {
        return $this->categoryRepository->getAllWithRelations();
    }

    /**
     * Get paginated categories with relations
     */
    public function paginateWithRelations(int $perPage = 50): LengthAwarePaginator
    {
        return $this->categoryRepository->paginateWithRelations($perPage);
    }

    /**
     * Create category with relations in a transaction
     */
    public function createWithRelations(array $data, SupportCollection $properties, SupportCollection $subcategories): InventoryCategory
    {
        return DB::transaction(function () use ($data, $properties, $subcategories) {
            $category = $this->categoryRepository->create($data);

            // Attach properties
            if ($properties->isNotEmpty()) {
                $this->categoryRepository->attachProperties($category, $properties);
            }

            // Create subcategories
            if ($subcategories->isNotEmpty()) {
                $this->createSubcategories($category, $subcategories);
            }

            return $category->load(['properties', 'subcategories']);
        });
    }

    /**
     * Update category with relations in a transaction
     */
    public function updateWithRelations(
        InventoryCategory $category,
        array $data,
        SupportCollection $properties,
        SupportCollection $subcategories
    ): InventoryCategory {
        return DB::transaction(function () use ($category, $data, $properties, $subcategories) {
            $this->categoryRepository->update($category, $data);

            // Sync properties
            $this->categoryRepository->syncProperties($category, $properties);

            // Update subcategories
            $this->updateSubcategories($category, $subcategories);

            return $category->fresh(['properties', 'subcategories']);
        });
    }

    /**
     * Create subcategories for a category
     */
    protected function createSubcategories(InventoryCategory $category, SupportCollection $subcategories): void
    {
        foreach ($subcategories as $subcategoryData) {
            // Create the subcategory
            $subcategory = $category->subcategories()->create([
                'name' => $subcategoryData['name']
            ]);

            // Attach properties to the subcategory if they exist
            if (isset($subcategoryData['properties']) && is_array($subcategoryData['properties'])) {
                $propertyData = collect($subcategoryData['properties'])->mapWithKeys(function ($property) {
                    return [$property['id'] => ['value' => $property['defaultValue'] ?? '']];
                });

                $subcategory->properties()->sync($propertyData);
            }
        }
    }

    /**
     * Update subcategories efficiently
     */
    protected function updateSubcategories(InventoryCategory $category, SupportCollection $subcategories): void
    {
        $existingIds = $subcategories->pluck('id')->filter()->toArray();

        // Delete removed subcategories
        $category->subcategories()
            ->whereNotIn('id', $existingIds)
            ->delete();

        // Update or create subcategories
        foreach ($subcategories as $subcategoryData) {
            if (isset($subcategoryData['id'])) {
                // Update existing subcategory
                $subcategory = $category->subcategories()
                    ->where('id', $subcategoryData['id'])
                    ->first();

                if ($subcategory) {
                    $subcategory->update(['name' => $subcategoryData['name']]);

                    // Sync properties for the subcategory
                    if (isset($subcategoryData['properties']) && is_array($subcategoryData['properties'])) {
                        $propertyData = collect($subcategoryData['properties'])->mapWithKeys(function ($property) {
                            return [$property['id'] => ['value' => $property['defaultValue'] ?? '']];
                        });

                        $subcategory->properties()->sync($propertyData);
                    }
                }
            } else {
                // Create new subcategory
                $subcategory = $category->subcategories()->create([
                    'name' => $subcategoryData['name']
                ]);

                // Attach properties to the new subcategory
                if (isset($subcategoryData['properties']) && is_array($subcategoryData['properties'])) {
                    $propertyData = collect($subcategoryData['properties'])->mapWithKeys(function ($property) {
                        return [$property['id'] => ['value' => $property['defaultValue'] ?? '']];
                    });

                    $subcategory->properties()->sync($propertyData);
                }
            }
        }
    }
}
