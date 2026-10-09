<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftPresetTimeline>
 */
class ShiftPresetTimelineFactory extends Factory
{
    protected $model = ShiftPresetTimeline::class;

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
