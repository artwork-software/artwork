<?php

namespace Database\Factories\Artwork\Modules\Vacation\Models;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacationConflictFactory extends Factory
{
    protected $model = VacationConflict::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacation_id' => Vacation::factory(),
            'shift_id' => Shift::factory(),
            'user_name' => $this->faker->name(),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'date' => today(),
        ];
    }
}
