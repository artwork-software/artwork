<?php

namespace Database\Factories\Artwork\Modules\MaterialSet\Models;

use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialSet>
 */
class MaterialSetFactory extends Factory
{
    protected $model = MaterialSet::class;

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
