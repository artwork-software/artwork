<?php

namespace Database\Factories\Artwork\Modules\Availability\Models;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityFactory extends Factory
{
    protected $model = Availability::class;

    public function definition(): array
    {
        return [
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'date' => $this->faker->date(),
            'full_day' => $this->faker->boolean(),
            'comment' => $this->faker->text(20),
            'is_series' => $this->faker->boolean(),
            'series_id' => $this->faker->randomDigitNotNull(),
            'available_type' => $this->faker->randomElement([User::class, Freelancer::class]),
            'available_id' => $this->faker->randomDigitNotNull(),
        ];
    }
}
