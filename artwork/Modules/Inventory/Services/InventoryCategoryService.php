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

    protected function normalizeValue(mixed $v): string
    {
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if ($v === 'true' || $v === 'false') {
            return (string) $v;
        }
        return (string) ($v ?? '');
    }

    /**
     * Baut das Sync-Payload: [property_id => ['value' => ..., 'position' => i]]
     * – entfernt Duplikate (erste Vorkommnis gewinnt)
     * – nutzt vorhandene 'position' oder ansonsten den Array-Index
     * * @param array|SupportCollection $properties
     * @return array<int, array{value: string, position: int}>
     */
    protected function buildPropertyPivotPayload(array|SupportCollection $properties): array
    {
        $payload = [];
        $seen = [];

        foreach (collect($properties)->filter(fn ($p) => isset($p['id']))->values() as $i => $p) {
            $id = (int) $p['id'];
            if (isset($seen[$id])) {
                continue; // Duplikat ignorieren
            }
            $seen[$id] = true;

            $payload[$id] = [
                'value'    => $this->normalizeValue($p['defaultValue'] ?? null),
                'position' => isset($p['position']) ? (int) $p['position'] : $i,
            ];
        }

        return $payload;
    }

    /**
     * Create subcategories for a category
     */
    protected function createSubcategories(InventoryCategory $category, SupportCollection $subcategories): void
    {
        foreach ($subcategories as $subcategoryData) {
            $subcategory = $category->subCategories()->create([
                'name' => (string) ($subcategoryData['name'] ?? ''),
            ]);

            if (!empty($subcategoryData['properties']) && is_array($subcategoryData['properties'])) {
                $propertyData = $this->buildPropertyPivotPayload($subcategoryData['properties']);
                $subcategory->properties()->sync($propertyData);
            }
        }
    }

    /**
     * Update subcategories efficiently (inkl. Property-Positionen)
     */
    protected function updateSubcategories(InventoryCategory $category, SupportCollection $subcategories): void
    {
        // Wenn keine Subcategories im Request => alle löschen
        if ($subcategories->isEmpty()) {
            $category->subCategories()->delete();
            return;
        }

        $existingIds = $subcategories->pluck('id')->filter()->map(fn ($v) => (int) $v)->all();

        // Entferne Subcategories, die nicht mehr im Request sind
        $category->subCategories()
            ->when(!empty($existingIds), fn ($q) => $q->whereNotIn('id', $existingIds))
            ->when(empty($existingIds), fn ($q) => $q) // explizit keine Bedingung -> löscht nichts hier
            ->delete();

        foreach ($subcategories as $subcategoryData) {
            $name = (string) ($subcategoryData['name'] ?? '');

            // Update oder Create je nach Vorhandensein der ID
            if (!empty($subcategoryData['id'])) {
                $subcategory = $category->subCategories()->where('id', (int) $subcategoryData['id'])->first();

                if ($subcategory) {
                    $subcategory->update(['name' => $name]);
                } else {
                    // Fallback: wenn ID nicht (mehr) existiert, neu anlegen
                    $subcategory = $category->subCategories()->create(['name' => $name]);
                }
            } else {
                $subcategory = $category->subCategories()->create(['name' => $name]);
            }

            // Properties synchronisieren (Werte + Position)
            if (!empty($subcategoryData['properties']) && is_array($subcategoryData['properties'])) {
                $propertyData = $this->buildPropertyPivotPayload($subcategoryData['properties']);
                $subcategory->properties()->sync($propertyData);
            } else {
                // Keine Properties im Request => alle entfernen
                $subcategory->properties()->detach();
            }
        }
    }

    public function paginateForApi(int $perPage = 50): LengthAwarePaginator
    {
        return $this->categoryRepository->paginateWithRelations($perPage);
    }
}
