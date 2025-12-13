<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Enums\InventoryPropertyTypeEnum;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CraftItemMigrationService
{
    public function migrateCraftItemsToInventoryArticles(): array {
        Log::info('Starting migration of craft inventory items to inventory articles');

        $craftItems = CraftInventoryItem::with(['cells.column', 'group'])->get();
        Log::info("Found {$craftItems->count()} craft inventory items to migrate.");

        $columnToPropertyMap = $this->createColumnToPropertyMap();
        $groupToCategoryMap = $this->createGroupToCategoryMap();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $totalCount = $craftItems->count();

        foreach ($craftItems as $craftItem) {
            try {
                $categoryInfo = $groupToCategoryMap[$craftItem->craft_inventory_group_id] ?? null;
                if (!$categoryInfo) {
                    Log::warning("No category mapping found for group ID: {$craftItem->craft_inventory_group_id}, using default 'Allgemein' category");
                    // Use default "Allgemein" category
                    $categoryInfo = $this->getDefaultCategoryMapping();
                }

                $nameCell = $craftItem->cells->first(function ($cell) {
                    return $cell->column->name === 'Name_Artikelbeschreibung';
                });

                if (!$nameCell) {
                    $nameCell = $craftItem->cells->first(function ($cell) {
                        return $cell->column->name === 'Name' || $cell->column->order === 0;
                    });
                }

                $name = $nameCell->cell_value ?? "Unnamed Item {$craftItem->id}";

                if ($this->isCraftItemAlreadyMigrated($name, $categoryInfo['category_id'], $categoryInfo['subcategory_id'])) {
                    Log::info("Skipping craft inventory item {$craftItem->id} as it has already been migrated");
                    $skippedCount++;
                    continue;
                }

                DB::beginTransaction();

                $descriptionCell = $craftItem->cells->first(function ($cell) {
                    return $cell->column->name === 'Description' || $cell->column->name === 'Beschreibung';
                });

                $quantityCell = $craftItem->cells->first(function ($cell) {
                    return $cell->column->name === 'Quantity' || $cell->column->name === 'Menge' ||
                           $cell->column->name === 'Anzahl' || $cell->column->type === CraftsInventoryColumnTypeEnum::NUMBER->value;
                });

                $article = new InventoryArticle();
                $article->name = $name;
                $article->description = $descriptionCell->cell_value ?? null;
                $article->quantity = $quantityCell ? (int)$quantityCell->cell_value : 0;
                $article->is_detailed_quantity = false;
                $article->inventory_category_id = $categoryInfo['category_id'];
                $article->inventory_sub_category_id = $categoryInfo['subcategory_id'];
                $article->save();

                $imageCell = $craftItem->cells->first(function ($cell) {
                    return $cell->column->name === 'Bild';
                });

                if ($article->id && $imageCell && !empty($imageCell->cell_value)) {
                    $article->images()->create([
                        'image' => $imageCell->cell_value,
                        'is_main_image' => true,
                        'order' => 0
                    ]);
                }

                foreach ($craftItem->cells as $cell) {
                    // Skip cells that were already processed
                    if (($cell->column->name === 'Name' || $cell->column->name === 'Name_Artikelbeschreibung' || $cell->column->order === 0) ||
                        ($cell->column->name === 'Description' || $cell->column->name === 'Beschreibung') ||
                        ($cell->column->name === 'Quantity' || $cell->column->name === 'Menge' ||
                         $cell->column->name === 'Anzahl' || $cell->column->type === CraftsInventoryColumnTypeEnum::NUMBER->value) ||
                        ($cell->column->name === 'Bild')) {
                        continue;
                    }

                    $propertyType = $columnToPropertyMap[$cell->column->type] ?? InventoryPropertyTypeEnum::TEXT->value;
                    $property = $this->findOrCreateProperty($cell->column->name, $propertyType);

                    if ($property && !empty($cell->cell_value)) {
                        InventoryPropertyValue::create([
                            'inventory_propertyable_type' => InventoryArticle::class,
                            'inventory_propertyable_id' => $article->id,
                            'inventory_article_property_id' => $property->id,
                            'value' => $cell->cell_value,
                        ]);
                    }
                }

                DB::commit();
                $successCount++;
                Log::info("Migrated craft inventory item {$craftItem->id} to inventory article {$article->id}");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error migrating craft inventory item {$craftItem->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        Log::info("Migration completed! Successfully migrated: {$successCount} items");
        if ($skippedCount > 0) {
            Log::info("Skipped: {$skippedCount} items (already migrated)");
        }
        if ($errorCount > 0) {
            Log::warning("Failed to migrate: {$errorCount} items");
        }

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'skipped_count' => $skippedCount,
            'total_count' => $totalCount,
        ];
    }

    private function createColumnToPropertyMap(): array
    {
        return [
            CraftsInventoryColumnTypeEnum::TEXT->value => InventoryPropertyTypeEnum::TEXT->value,
            CraftsInventoryColumnTypeEnum::DATE->value => InventoryPropertyTypeEnum::DATE->value,
            CraftsInventoryColumnTypeEnum::CHECKBOX->value => InventoryPropertyTypeEnum::CHECKBOX->value,
            CraftsInventoryColumnTypeEnum::SELECT->value => InventoryPropertyTypeEnum::SELECT->value,
            CraftsInventoryColumnTypeEnum::NUMBER->value => InventoryPropertyTypeEnum::NUMBER->value,
            CraftsInventoryColumnTypeEnum::UPLOAD->value => InventoryPropertyTypeEnum::FILE->value,
            CraftsInventoryColumnTypeEnum::LAST_EDIT_AND_EDITOR->value => InventoryPropertyTypeEnum::TEXT->value,
        ];
    }

    private function createGroupToCategoryMap(): array
    {
        $map = [];

        $groups = CraftInventoryGroup::all();

        foreach ($groups as $group) {
            $category = InventoryCategory::firstOrCreate(
                ['name' => $group->name],
                ['description' => "Migrated from craft inventory group {$group->id}"]
            );

            $subcategory = null;
            if ($group->parent_id) {
                $parentGroup = CraftInventoryGroup::find($group->parent_id);
                if ($parentGroup) {
                    $subcategory = InventorySubCategory::firstOrCreate(
                        [
                            'name' => $group->name,
                            'inventory_category_id' => $category->id
                        ],
                        ['description' => "Migrated from craft inventory group {$group->id}"]
                    );
                }
            }

            $map[$group->id] = [
                'category_id' => $category->id,
                'subcategory_id' => $subcategory ? $subcategory->id : null,
            ];
        }

        return $map;
    }

    private function findOrCreateProperty(string $name, string $type): ?InventoryArticleProperties
    {
        // Find or create the property
        return InventoryArticleProperties::firstOrCreate(
            ['name' => $name],
            [
                'type' => $type,
                'select_values' => $type === 'select' ? [] : null,
                'is_filterable' => true,
                'show_in_list' => true,
                'tooltip_text' => "Migrated from craft inventory column: {$name}",
                'is_required' => false,
                'is_deletable' => true,
            ]
        );
    }

    private function isCraftItemAlreadyMigrated(string $name, int $categoryId, ?int $subcategoryId = null): bool
    {
        $query = InventoryArticle::withTrashed()->where('name', $name)
            ->where('inventory_category_id', $categoryId);

        if ($subcategoryId !== null) {
            $query->where('inventory_sub_category_id', $subcategoryId);
        }

        return $query->exists();
    }

    private function getDefaultCategoryMapping(): array
    {
        // Find or create the category
        $category = InventoryCategory::firstOrCreate(
            ['name' => 'Allgemein'],
            ['description' => 'Default category for items with no mapping']
        );

        return [
            'category_id' => $category->id,
            'subcategory_id' => null, // No subcategory for default category
        ];
    }
}
