<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\SageAssignedData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SageAssignedData>
 */
class SageAssignedDataFactory extends Factory
{
    protected $model = SageAssignedData::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sage_id' => fake()->numberBetween(1, 1000),
            'tan' => fake()->numberBetween(1, 1000),
            'periode' => fake()->numberBetween(1, 1000),
            'kto_haben' => fake()->words(2, true),
            'kreditor' => fake()->words(2, true),
            'buchungstext' => fake()->words(2, true),
            'buchungsbetrag' => fake()->randomFloat(2, 0, 1000),
            'belegnummer' => fake()->words(2, true),
            'belegdatum' => fake()->words(2, true),
            'kto_soll' => fake()->words(2, true),
            'sa_kto' => fake()->words(2, true),
            'kst_traeger' => fake()->words(2, true),
            'kst_stelle' => fake()->words(2, true),
            'buchungsdatum' => fake()->words(2, true),
        ];
    }
}
