<?php

namespace Database\Factories;

use App\Models\RoomAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
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
