<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftPreset>
 */
class ShiftPresetFactory extends Factory
{
    protected $model = ShiftPreset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
        ];
    }
}
