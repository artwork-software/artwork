<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTab>
 */
class ProjectTabFactory extends Factory
{
    protected $model = ProjectTab::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => substr('Tab ' . $this->faker->unique()->word(), 0, 30),
            'order' => $this->faker->numberBetween(1, 100),
            'default' => false,
            'visible_for_all' => true,
        ];
    }
}
