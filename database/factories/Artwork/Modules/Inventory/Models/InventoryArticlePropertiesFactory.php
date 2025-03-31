<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Artwork\Modules\Inventory\Models\InventoryArticleProperties>
 */
class InventoryArticlePropertiesFactory extends Factory
{
    protected $model = InventoryArticleProperties::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement(['string', 'number', 'boolean']),
            'is_filterable' => fake()->boolean(),
            'show_in_list' => fake()->boolean(),
        ];
    }
}
