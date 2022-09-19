<?php

namespace Database\Factories;

use App\Models\EventType;
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
        $startTime = now()->startOfMonth()->addHours($this->faker->numberBetween(1, 24 * 30));

        return [
            'name' => $this->faker->text(20),
            'description' => $this->faker->text,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $startTime->addHours($this->faker->numberBetween(1, 8))->toDateTimeString(),
            'occupancy_option' => $this->faker->boolean(0),
            'audience' => $this->faker->boolean(0),
            'is_loud' => $this->faker->boolean(0),
            'event_type_id' => EventType::factory(),
            'project_id' => null,
            'user_id' => User::factory(),
        ];
    }
}
