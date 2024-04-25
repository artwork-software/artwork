<?php

namespace Database\Factories\Artwork\Modules\Room\Models;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'area_id' => Area::factory(),
            'user_id' => User::factory(),
            'order' => 1,
        ];
    }
}
