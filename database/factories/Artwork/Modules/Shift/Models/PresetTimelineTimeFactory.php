<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\PresetTimelineTime;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresetTimelineTime>
 */
class PresetTimelineTimeFactory extends Factory
{
    protected $model = PresetTimelineTime::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'preset_timeline_id' => ShiftPresetTimeline::factory(),
        ];
    }
}
