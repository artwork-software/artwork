<?php

namespace Database\Factories\Artwork\Modules\Accommodation\Models;

use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccommodationRoomType>
 */
class AccommodationRoomTypeFactory extends Factory
{
    protected $model = AccommodationRoomType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
        ];
    }
}
