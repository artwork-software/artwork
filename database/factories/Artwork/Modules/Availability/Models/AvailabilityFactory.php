<?php

namespace Database\Factories\Artwork\Modules\Availability\Models;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityFactory extends Factory
{
    protected $model = Availability::class;

    /**
     * @return string []
     */
    public function definition(): array
    {
        $availableType = $this->faker->randomElement([User::class, Freelancer::class]);

        return [
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'date' => $this->faker->date(),
            'full_day' => $this->faker->boolean(),
            'comment' => $this->faker->text(20),
            'is_series' => $this->faker->boolean(),
            'series_id' => null,
            'available_type' => $availableType,
            'available_id' => $availableType::factory(),
        ];
    }
}
