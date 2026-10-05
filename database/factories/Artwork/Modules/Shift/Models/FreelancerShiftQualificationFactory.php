<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Models\FreelancerShiftQualification;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreelancerShiftQualification>
 */
class FreelancerShiftQualificationFactory extends Factory
{
    protected $model = FreelancerShiftQualification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'freelancer_id' => Freelancer::factory(),
            'shift_qualification_id' => ShiftQualification::factory(),
        ];
    }
}
