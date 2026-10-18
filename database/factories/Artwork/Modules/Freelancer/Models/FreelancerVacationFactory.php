<?php

namespace Database\Factories\Artwork\Modules\Freelancer\Models;

use Artwork\Modules\Freelancer\Models\FreelancerVacation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreelancerVacation>
 */
class FreelancerVacationFactory extends Factory
{
    protected $model = FreelancerVacation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freelancer_id' => fake()->numberBetween(1, 1000),
            'from' => now()->toDateString(),
            'until' => now()->toDateString(),
        ];
    }
}
