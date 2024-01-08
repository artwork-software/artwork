<?php

namespace Database\Factories\Artwork\Modules\Room\Models;

use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Room>
    */
class RoomFactory extends Factory
{
    protected $model = Room::class;
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->colorName,
            'description' => $this->faker->paragraph,
            'temporary' => false,
            'start_date' => null,
            'end_date' => null,
            'area_id' => 1,
            'user_id' => 1,
            'order' => 1,
        ];
    }
}
