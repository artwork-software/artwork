<?php

namespace Database\Factories\Artwork\Modules\BusinessIntelligence\Models;

use Artwork\Modules\BusinessIntelligence\Models\BiExportPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiExportPreset>
 */
class BiExportPresetFactory extends Factory
{
    protected $model = BiExportPreset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'columns' => [],
        ];
    }
}
