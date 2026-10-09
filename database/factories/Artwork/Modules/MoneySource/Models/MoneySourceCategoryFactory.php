<?php

namespace Database\Factories\Artwork\Modules\MoneySource\Models;

use Artwork\Modules\MoneySource\Models\MoneySourceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoneySourceCategory>
 */
class MoneySourceCategoryFactory extends Factory
{
    protected $model = MoneySourceCategory::class;

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
