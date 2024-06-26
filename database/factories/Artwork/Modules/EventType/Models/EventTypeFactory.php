<?php

namespace Database\Factories\Artwork\Modules\EventType\Models;

use Artwork\Modules\EventType\Models\EventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventType>
 */
class EventTypeFactory extends Factory
{
    protected $model = EventType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'abbreviation' => $this->faker->title(),
            'hex_code' => $this->faker->hexColor,
            'project_mandatory' => $this->faker->boolean(0),
            'individual_name' => $this->faker->boolean(0),
        ];
    }
}
