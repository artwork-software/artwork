<?php

namespace Database\Factories;

use App\Models\ChecklistTemplate;
use App\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskTemplateFactory extends Factory
{
    protected $model = TaskTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->name,
            'done' => false,
            'checklist_template_id' => ChecklistTemplate::factory(),
        ];
    }
}
