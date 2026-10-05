<?php

namespace Database\Factories\Artwork\Modules\ExternalUserManagement\Models;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalUser>
 */
class ExternalUserFactory extends Factory
{
    protected $model = ExternalUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => fake()->numberBetween(1, 1000),
            'identification' => fake()->words(2, true),
        ];
    }
}
