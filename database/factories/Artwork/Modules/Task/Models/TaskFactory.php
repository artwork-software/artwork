<?php

namespace Database\Factories\Artwork\Modules\Task\Models;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Task\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->text,
            'deadline' => $this->faker->date,
            'done' => $this->faker->boolean(50),
            'order' => 1,
            'checklist_id' => Checklist::factory(),
        ];
    }
}
