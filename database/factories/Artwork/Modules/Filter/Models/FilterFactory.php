<?php

namespace Database\Factories\Artwork\Modules\Filter\Models;

use Artwork\Modules\Filter\Models\Filter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Filter>
 */
class FilterFactory extends Factory
{
    protected $model = Filter::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'user_id' => fake()->numberBetween(1, 1000),
        ];
    }
}
