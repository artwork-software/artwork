<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Artwork\Modules\Inventory\Models\InventoryCategory>
 */
class InventoryCategoryFactory extends Factory
{

    protected $model = InventoryCategory::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // category name
            'name' => fake()->unique()->word(),
        ];
    }
}
