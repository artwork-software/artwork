<?php

namespace Database\Factories\Artwork\Modules\WorkTime\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkTimeBooking>
 */
class WorkTimeBookingFactory extends Factory
{
    protected $model = WorkTimeBooking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
