<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftTimePreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftTimePreset>
 */
class ShiftTimePresetFactory extends Factory
{
    protected $model = ShiftTimePreset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'break_time' => 30,
        ];
    }
}
