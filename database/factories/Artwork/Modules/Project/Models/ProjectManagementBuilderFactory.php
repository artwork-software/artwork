<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectManagementBuilder>
 */
class ProjectManagementBuilderFactory extends Factory
{
    protected $model = ProjectManagementBuilder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Builder ' . $this->faker->unique()->word(),
            'order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'type' => 'text',
            'deletable' => true,
        ];
    }
}
