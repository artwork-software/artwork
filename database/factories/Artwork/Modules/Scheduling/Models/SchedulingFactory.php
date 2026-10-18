<?php

namespace Database\Factories\Artwork\Modules\Scheduling\Models;

use Artwork\Modules\Scheduling\Models\Scheduling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scheduling>
 */
class SchedulingFactory extends Factory
{
    protected $model = Scheduling::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 1000),
            'type' => fake()->words(2, true),
        ];
    }
}
