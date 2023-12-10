<?php

namespace Database\Factories;

use Artwork\Modules\Checklist\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
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
