<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftsQualifications>
 */
class ShiftsQualificationsFactory extends Factory
{
    protected $model = ShiftsQualifications::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'shift_qualification_id' => ShiftQualification::factory(),
            'value' => $this->faker->numberBetween(1, 5),
        ];
    }
}
