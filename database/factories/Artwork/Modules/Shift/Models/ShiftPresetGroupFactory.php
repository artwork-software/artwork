<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftPresetGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftPresetGroup>
 */
class ShiftPresetGroupFactory extends Factory
{
    protected $model = ShiftPresetGroup::class;

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
