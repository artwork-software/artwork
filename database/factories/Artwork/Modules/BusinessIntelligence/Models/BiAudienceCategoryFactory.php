<?php

namespace Database\Factories\Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiAudienceCategory>
 */
class BiAudienceCategoryFactory extends Factory
{
    protected $model = BiAudienceCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
        ];
    }
}
