<?php

namespace Database\Factories\Artwork\Modules\CollectingSociety\Models;

use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectingSociety>
 */
class CollectingSocietyFactory extends Factory
{
    protected $model = CollectingSociety::class;

    public function definition(): array
    {
        return [
            'name' => 'GEMA ' . $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
        ];
    }
}
