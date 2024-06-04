<?php

namespace Database\Factories\Artwork\Modules\ShiftTimePreset\Models;

use Artwork\Modules\ShiftTimePreset\Models\ShiftTimePreset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Random\RandomException;

class ShiftTimePresetFactory extends Factory
{
    protected $model = ShiftTimePreset::class;

    /**
     * @return array<string, mixed>
     * @throws RandomException
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'break_time' => random_int(0, 60),
        ];
    }
}
