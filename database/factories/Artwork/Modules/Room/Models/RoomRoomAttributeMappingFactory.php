<?php

namespace Database\Factories\Artwork\Modules\Room\Models;

use Artwork\Modules\Room\Models\RoomRoomAttributeMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomRoomAttributeMapping>
 */
class RoomRoomAttributeMappingFactory extends Factory
{
    protected $model = RoomRoomAttributeMapping::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => fake()->numberBetween(1, 1000),
            'room_attribute_id' => fake()->numberBetween(1, 1000),
        ];
    }
}
