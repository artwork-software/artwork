<?php

namespace Database\Factories\Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\ProjectContentExportTemplate;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectContentExportTemplate>
 */
class ProjectContentExportTemplateFactory extends Factory
{
    protected $model = ProjectContentExportTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Template ' . $this->faker->unique()->word(),
            'columns' => [
                ['key' => 'system:project_name', 'title' => null, 'show_label' => false],
                ['key' => 'system:events', 'title' => null, 'show_label' => false],
            ],
            'settings' => ['filters' => [], 'event_column' => ['event_type_ids' => []]],
            'created_by' => User::factory(),
        ];
    }
}
