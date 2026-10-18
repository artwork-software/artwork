<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryArticleImage>
 */
class InventoryArticleImageFactory extends Factory
{
    protected $model = InventoryArticleImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_article_id' => InventoryArticle::factory(),
            'image' => fake()->words(2, true),
        ];
    }
}
