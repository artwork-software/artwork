<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;
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
