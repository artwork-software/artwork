<?php

namespace Artwork\Modules\Inventory\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

readonly class InventoryCategoryRepository
{
    public function __construct(
        private InventoryCategory $inventoryCategory
    ) {
    }

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
                'subcategories' => function ($query): void {
                    $query
                        ->orderBy('name');
                },
                'subcategories.articles',
                'subcategories.properties:id,name,type,select_values',
                'properties' => function ($query): void {
                    $query
                        ->orderBy('name');
                },
                'articles' => function ($query): void {
                    $query
                        ->with([
                            'subCategory:id,name',
                            'images' => function ($q): void {
                                $q->where('is_main_image', true)->select('id', 'inventory_article_id', 'image');
                            }
                        ])
                        ->withCount('detailedArticleQuantities');
                }
            ])
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
                'subcategories.properties:id,name,type,select_values',
                'properties:id,name,type,select_values'
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

    private function normalizeValue(mixed $v): string
    {
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        return (string) ($v ?? '');
    }

    /**
     * Attach properties to category (anhängen = ans Ende einsortieren)
     */
    public function attachProperties(InventoryCategory $category, SupportCollection $properties): void
    {
        // existierende IDs ermitteln, damit wir nur NEUE anhängen
        $existingIds = DB::table('inventory_category_property_values')
            ->where('inventory_category_propertyable_type', $category->getMorphClass())
            ->where('inventory_category_propertyable_id', $category->getKey())
            ->pluck('inventory_article_property_id')
            ->all();

        // aktuelle Max-Position bestimmen
        $start = (int) DB::table('inventory_category_property_values')
            ->where('inventory_category_propertyable_type', $category->getMorphClass())
            ->where('inventory_category_propertyable_id', $category->getKey())
            ->max('position');
        if ($start < 0) {
            $start = -1;
        }

        $propertyData = $properties
            ->reject(fn ($p) => in_array($p['id'], $existingIds, true))
            ->values()
            ->mapWithKeys(function ($p, $i) use ($start) {
                $pos = array_key_exists('position', $p) ? (int) $p['position'] : ($start + 1 + $i);
                return [
                    $p['id'] => [
                        'value'    => $this->normalizeValue($p['defaultValue'] ?? null),
                        'position' => $pos,
                    ],
                ];
            });

        if ($propertyData->isNotEmpty()) {
            $category->properties()->syncWithoutDetaching($propertyData->all());
        }
    }

    /**
     * Sync properties with category (vollständige Liste = Reihenfolge nach Index)
     */
    public function syncProperties(InventoryCategory $category, SupportCollection $properties): void
    {
        $propertyData = $properties
            ->values()
            ->mapWithKeys(function ($p, $i) {
                $pos = array_key_exists('position', $p) ? (int) $p['position'] : $i;
                return [
                    $p['id'] => [
                        'value'    => $this->normalizeValue($p['defaultValue'] ?? null),
                        'position' => $pos,
                    ],
                ];
            });

        $category->properties()->sync($propertyData->all());
    }
}
