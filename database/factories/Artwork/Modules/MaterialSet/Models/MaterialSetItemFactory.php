<?php

namespace Database\Factories\Artwork\Modules\MaterialSet\Models;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\MaterialSet\Models\MaterialSetItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialSetItem>
 */
class MaterialSetItemFactory extends Factory
{
    protected $model = MaterialSetItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_set_id' => MaterialSet::factory(),
            'inventory_article_id' => InventoryArticle::factory(),
        ];
    }
}
