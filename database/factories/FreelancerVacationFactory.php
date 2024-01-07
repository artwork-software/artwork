<?php

namespace Database\Factories;

use App\Models\Freelancer;
use App\Models\FreelancerVacation;
use Illuminate\Database\Eloquent\Factories\Factory;

class FreelancerVacationFactory extends Factory
{
    protected $model = FreelancerVacation::class;

    public function definition(): array
    {
        return [
            'from' => today(),
            'until' => today()->addDay(),
            'freelancer_id' => Freelancer::factory(),
        ];
    }
}
