<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentInTab>
 */
class ComponentInTabFactory extends Factory
{
    protected $model = ComponentInTab::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_tab_id' => ProjectTab::factory(),
            'component_id' => Component::factory(),
            'order' => fake()->numberBetween(1, 1000),
        ];
    }
}
