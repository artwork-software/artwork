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
        $subcategoriesData = $subcategories->map(function ($subcategory) use ($category) {
            return [
                'name' => $subcategory['name'],
                'inventory_category_id' => $category->id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        })->toArray();
        
        // Bulk insert für bessere Performance
        if (!empty($subcategoriesData)) {
            $category->subcategories()->insert($subcategoriesData);
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
                $category->subcategories()
                    ->where('id', $subcategoryData['id'])
                    ->update(['name' => $subcategoryData['name']]);
            } else {
                $category->subcategories()->create([
                    'name' => $subcategoryData['name']
                ]);
            }
        }
    }
}
