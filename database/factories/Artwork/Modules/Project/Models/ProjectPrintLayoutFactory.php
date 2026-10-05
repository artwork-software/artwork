<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectPrintLayout>
 */
class ProjectPrintLayoutFactory extends Factory
{
    protected $model = ProjectPrintLayout::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'order' => fake()->numberBetween(1, 1000),
            'user_id' => User::factory(),
        ];
    }
}
