<?php

namespace Database\Factories\Artwork\Modules\ExternalUserManagement\Models;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalUserSource>
 */
class ExternalUserSourceFactory extends Factory
{
    protected $model = ExternalUserSource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'type' => fake()->words(2, true),
        ];
    }
}
