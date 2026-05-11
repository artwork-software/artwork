<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\ProjectRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectRole>
 */
class ProjectRoleFactory extends Factory
{
    protected $model = ProjectRole::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Role ' . $this->faker->unique()->word(),
        ];
    }
}
