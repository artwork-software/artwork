<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RoomAttribute;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Room>
 */
class RoomAttributeFactory extends Factory
{
    protected $model = RoomAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->randomElement([
                'seating',
                'hallway',
                'big stage',
                'fits orchestra',
                'offers stage design',
                'child friendly',
                'wheelchair friendly'
            ]),
        ];
    }
}
