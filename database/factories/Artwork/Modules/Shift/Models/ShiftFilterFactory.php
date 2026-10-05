<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftFilter>
 */
class ShiftFilterFactory extends Factory
{
    protected $model = ShiftFilter::class;

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
