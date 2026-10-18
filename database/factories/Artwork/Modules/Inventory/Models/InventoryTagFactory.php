<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTag>
 */
class InventoryTagFactory extends Factory
{
    protected $model = InventoryTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
        ];
    }
}
