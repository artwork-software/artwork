<?php

namespace Database\Factories\Artwork\Modules\Craft\Models;

use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Database\Eloquent\Factories\Factory;

class CraftFactory extends Factory
{
    protected $model = Craft::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'abbreviation' => $this->faker->jobTitle()
        ];
    }
}
