<?php

namespace Database\Factories\Artwork\Modules\Room\Models;

use Artwork\Modules\Room\Models\RoomRoomCategoryMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomRoomCategoryMapping>
 */
class RoomRoomCategoryMappingFactory extends Factory
{
    protected $model = RoomRoomCategoryMapping::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => fake()->numberBetween(1, 1000),
            'room_category_id' => fake()->numberBetween(1, 1000),
        ];
    }
}
