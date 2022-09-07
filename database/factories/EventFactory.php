<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
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
            'description' => $this->faker->paragraph,
            'start_time' => $this->faker->dateTime,
            'end_time' => $this->faker->dateTime,
            'occupancy_option' => $this->faker->boolean(0),
            'audience' => $this->faker->boolean(0),
            'is_loud' => $this->faker->boolean(0),
            'event_type_id' => 1,
            'room_id' => 1,
            'project_id' => null,
            'user_id' => User::factory(),
        ];
    }
}
