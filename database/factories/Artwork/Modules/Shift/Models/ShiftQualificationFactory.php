<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShiftQualification>
 */
class ShiftQualificationFactory extends Factory
{
    protected $model = ShiftQualification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'icon' => 'user-icon',
            'name' => Str::ucfirst($this->faker->unique()->word()),
            'available' => true,
        ];
    }
}
