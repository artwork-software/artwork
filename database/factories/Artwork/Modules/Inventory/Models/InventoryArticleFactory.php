<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Artwork\Modules\Inventory\Models\InventoryArticle>
 */
class InventoryArticleFactory extends Factory
{

    protected $model = InventoryArticle::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'quantity' => fake()->randomFloat(2, 0, 100),
            'inventory_category_id' => null, // Wird im Seeder gesetzt
            'inventory_sub_category_id' => null, // Wird im Seeder gesetzt
        ];
    }
}
