<?php

namespace Database\Factories\Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryArticleStatus>
 */
class InventoryArticleStatusFactory extends Factory
{
    protected $model = InventoryArticleStatus::class;

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
