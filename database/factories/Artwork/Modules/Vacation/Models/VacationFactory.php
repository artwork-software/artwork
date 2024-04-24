<?php

namespace Database\Factories\Artwork\Modules\Vacation\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacationFactory extends Factory
{
    protected $model = Vacation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacationer_type' => User::class,
            'vacationer_id' => User::factory(),
            'date' => today(),
            'full_day' => $this->faker->boolean(),
            'is_series' => $this->faker->boolean(),
        ];
    }
}
