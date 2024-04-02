<?php

namespace Database\Factories\Artwork\Modules\ShiftPreset\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftPresetFactory extends Factory
{
    protected $model = ShiftPreset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'event_type_id' => Event::factory(),
        ];
    }
}
