<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SidebarTabComponent>
 */
class SidebarTabComponentFactory extends Factory
{
    protected $model = SidebarTabComponent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_tab_sidebar_id' => ProjectTabSidebarTab::factory(),
            'component_id' => Component::factory(),
            'order' => fake()->numberBetween(1, 1000),
        ];
    }
}
