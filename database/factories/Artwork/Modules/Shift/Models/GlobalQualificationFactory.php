<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\GlobalQualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GlobalQualification>
 */
class GlobalQualificationFactory extends Factory
{
    protected $model = GlobalQualification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'icon' => 'IconStar',
        ];
    }
}
