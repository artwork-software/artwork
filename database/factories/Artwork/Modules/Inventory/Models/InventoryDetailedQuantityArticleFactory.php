<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle>
 */
class InventoryDetailedQuantityArticleFactory extends Factory
{
    protected $model = InventoryDetailedQuantityArticle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_article_id' => InventoryArticle::factory()->state(['is_detailed_quantity' => true]),
            'name' => fake()->word(),
            'description' => null,
            'quantity' => 1,
            'detail_number' => fake()->unique()->numberBetween(1, 1_000_000),
            'external_id' => fake()->unique()->bothify('EXT-####-????'),
            'inventory_number' => fake()->unique()->bothify('INV-####-????'),
        ];
    }
}
