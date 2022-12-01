<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => 'Project ' . $this->faker->firstNameFemale,
            'description' => $this->faker->paragraph,
            'cost_center' => $this->faker->name,
            'number_of_participants' => $this->faker->numberBetween(5, 500)
        ];
    }
}
