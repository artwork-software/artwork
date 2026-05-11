<?php

namespace Database\Factories\Artwork\Modules\Event\Models;

use Artwork\Modules\Event\Models\EventStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventStatus>
 */
class EventStatusFactory extends Factory
{
    protected $model = EventStatus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
            'order' => $this->faker->numberBetween(1, 100),
            'default' => false,
        ];
    }
}
