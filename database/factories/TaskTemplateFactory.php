<?php

namespace Database\Factories;

use App\Models\ChecklistTemplate;
use App\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskTemplateFactory extends Factory
{
    protected $model = TaskTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->name,
            'done' => false,
            'checklist_template_id' => ChecklistTemplate::factory(),
        ];
    }
}
