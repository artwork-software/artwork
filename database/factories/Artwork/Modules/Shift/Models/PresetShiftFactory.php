<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\PresetShift;
use Artwork\Modules\Shift\Models\ShiftPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresetShift>
 */
class PresetShiftFactory extends Factory
{
    protected $model = PresetShift::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_preset_id' => ShiftPreset::factory(),
            'start' => '08:00',
            'end' => '16:00',
            'break_minutes' => 30,
            'craft_id' => Craft::factory(),
            'description' => $this->faker->sentence(3),
        ];
    }
}
