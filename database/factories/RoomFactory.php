<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->colorName,
            'description' => $this->faker->paragraph,
            'temporary' => false,
            'start_date' => null,
            'end_date' => null,
            'area_id' => 1,
            'user_id' => 1,
            'order' => 1,
        ];
    }
}
