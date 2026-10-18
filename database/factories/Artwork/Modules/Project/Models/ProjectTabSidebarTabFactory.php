<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTabSidebarTab>
 */
class ProjectTabSidebarTabFactory extends Factory
{
    protected $model = ProjectTabSidebarTab::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_tab_id' => ProjectTab::factory(),
            'name' => fake()->words(2, true),
            'order' => fake()->numberBetween(1, 1000),
        ];
    }
}
