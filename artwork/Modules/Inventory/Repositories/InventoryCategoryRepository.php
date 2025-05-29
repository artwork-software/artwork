<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

readonly class InventoryCategoryRepository
{
    public function __construct(
        private InventoryCategory $inventoryCategory
    ) {}

    public function getNewModelInstance(array $attributes = []): InventoryCategory
    {
        return $this->inventoryCategory->newInstance($attributes);
    }

    public function getNewModelQuery(): Builder
    {
        return $this->inventoryCategory->newModelQuery();
    }

    /**
     * Get all categories with optimized eager loading
     */
    public function getAllWithRelations(): Collection
    {
        return $this->getNewModelQuery()
            ->with([
                'subcategories' => function ($query) {
                    $query->select('id', 'inventory_category_id', 'name')
                        ->orderBy('name');
                },
                'subcategories.articles',
                'properties' => function ($query) {
                    $query->select('inventory_article_properties.id', 'name', 'type')
                        ->orderBy('name');
                },
                'articles' => function ($query) {
                    $query->select('id', 'inventory_category_id', 'inventory_sub_category_id', 'name')
                        ->with([
                            'subCategory:id,name',
                            'images' => function ($q) {
                                $q->where('is_main_image', true)->select('id', 'inventory_article_id', 'image');
                            }
                        ])
                        ->withCount('detailedArticleQuantities');
                }
            ])
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get paginated categories with relations
     */
    public function paginateWithRelations(int $perPage): LengthAwarePaginator
    {
        return $this->getNewModelQuery()
            ->with([
                'subcategories:id,inventory_category_id,name',
                'properties:id,name,type'
            ])
            ->select('id', 'name')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new category
     */
    public function create(array $data): InventoryCategory
    {
        $category = $this->getNewModelInstance($data);
        $category->save();
        return $category;
    }

    /**
     * Update a category
     */
    public function update(InventoryCategory $category, array $data): bool
    {
        return $category->update($data);
    }

    /**
     * Attach properties to category
     */
    public function attachProperties(InventoryCategory $category, SupportCollection $properties): void
    {
        $propertyIds = $properties->pluck('id')->filter()->toArray();
        $category->properties()->syncWithoutDetaching($propertyIds);
    }

    /**
     * Sync properties with category
     */
    public function syncProperties(InventoryCategory $category, SupportCollection $properties): void
    {
        $propertyIds = $properties->pluck('id')->filter()->toArray();
        $category->properties()->sync($propertyIds);
    }
}
