<?php

namespace Database\Factories\Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiEventTypeTag>
 */
class BiEventTypeTagFactory extends Factory
{
    protected $model = BiEventTypeTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'name_de' => fake()->words(2, true),
        ];
    }
}
