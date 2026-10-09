<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryTagGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTagGroup>
 */
class InventoryTagGroupFactory extends Factory
{
    protected $model = InventoryTagGroup::class;

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
