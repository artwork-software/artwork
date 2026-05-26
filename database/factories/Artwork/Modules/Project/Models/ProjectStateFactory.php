<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\ProjectState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectState>
 */
class ProjectStateFactory extends Factory
{
    protected $model = ProjectState::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'State ' . $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
            'is_planning' => false,
        ];
    }
}
