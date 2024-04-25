<?php

namespace Database\Factories\Artwork\Modules\UserShiftCalendarFilter\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserShiftCalendarFilter\Models\UserShiftCalendarFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserShiftCalendarFilterFactory extends Factory
{
    protected $model = UserShiftCalendarFilter::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_types' => [],
            'rooms' => [],
        ];
    }
}
