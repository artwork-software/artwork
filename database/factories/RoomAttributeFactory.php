<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomAttributeFactory extends Factory
{
    protected $model = RoomAttribute::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
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
