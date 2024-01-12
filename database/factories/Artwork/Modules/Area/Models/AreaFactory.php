<?php

namespace Database\Factories\Artwork\Modules\Area\Models;

use Artwork\Modules\Area\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Area ' . $this->faker->colorName
        ];
    }
}
