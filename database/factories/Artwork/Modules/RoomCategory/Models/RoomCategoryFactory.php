<?php

namespace Database\Factories\Artwork\Modules\RoomCategory\Models;

use Artwork\Modules\RoomCategory\Models\RoomCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomCategoryFactory extends Factory
{
    protected $model = RoomCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => $this->faker->name,
        ];
    }
}
