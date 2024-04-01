<?php

namespace Database\Factories\Artwork\Modules\ShiftPresetTimeline\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftPresetTimeline\Models\ShiftPresetTimeline;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftPresetTimelineFactory extends Factory
{
    protected $model = ShiftPresetTimeline::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
           'shift_preset_id' => ShiftPreset::factory(),
            'start' => $this->faker->time(),
            'end' => $this->faker->time(),
            'description' => $this->faker->sentence(),
        ];
    }
}
