<?php

namespace Database\Factories\Artwork\Modules\Genre\Models;

use Artwork\Modules\Genre\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Genre>
 */
class GenreFactory extends Factory
{
    protected $model = Genre::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Genre ' . $this->faker->colorName
        ];
    }
}
