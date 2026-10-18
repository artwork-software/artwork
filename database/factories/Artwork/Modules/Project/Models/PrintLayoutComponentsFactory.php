<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrintLayoutComponents>
 */
class PrintLayoutComponentsFactory extends Factory
{
    protected $model = PrintLayoutComponents::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_print_layout_id' => ProjectPrintLayout::factory(),
            'component_id' => Component::factory(),
            'position' => fake()->numberBetween(1, 1000),
            'row' => fake()->numberBetween(1, 1000),
        ];
    }
}
