<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisclosureComponents>
 */
class DisclosureComponentsFactory extends Factory
{
    protected $model = DisclosureComponents::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disclosure_id' => Component::factory(),
            'component_id' => Component::factory(),
            'order' => fake()->numberBetween(1, 1000),
        ];
    }
}
