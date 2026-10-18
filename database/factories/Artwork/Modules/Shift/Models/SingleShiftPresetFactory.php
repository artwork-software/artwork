<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SingleShiftPreset>
 */
class SingleShiftPresetFactory extends Factory
{
    protected $model = SingleShiftPreset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'start_time' => '10:00:00',
            'end_time' => '10:00:00',
        ];
    }
}
