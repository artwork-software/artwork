<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectComponentValue>
 */
class ProjectComponentValueFactory extends Factory
{
    protected $model = ProjectComponentValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'component_id' => Component::factory(),
            'data' => [],
        ];
    }
}
