<?php

namespace Database\Factories\Artwork\Modules\Availability\Models;

use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilitiesConflict>
 */
class AvailabilitiesConflictFactory extends Factory
{
    protected $model = AvailabilitiesConflict::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'availability_id' => Availability::factory(),
            'shift_id' => Shift::factory(),
            'user_name' => fake()->words(2, true),
            'date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:00:00',
        ];
    }
}
