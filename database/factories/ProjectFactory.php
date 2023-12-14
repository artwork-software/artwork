<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Project ' . $this->faker->firstNameFemale,
            'description' => $this->faker->paragraph,
            'number_of_participants' => $this->faker->numberBetween(5, 500)
        ];
    }
}
