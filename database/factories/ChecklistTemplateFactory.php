<?php

namespace Database\Factories;

use App\Models\ChecklistTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistTemplate>
 */
class ChecklistTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'user_id' => 1
        ];
    }
}
