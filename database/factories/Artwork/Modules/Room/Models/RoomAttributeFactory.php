<?php

namespace Database\Factories\Artwork\Modules\Room\Models;

use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\RoomAttribute\Models\RoomAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 *
 * @method RoomAttribute createOne(array $attributes = [])
 * @method RoomAttribute[] create(array $attributes = [])
 * @method RoomAttribute makeOne(array $attributes = [])
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
