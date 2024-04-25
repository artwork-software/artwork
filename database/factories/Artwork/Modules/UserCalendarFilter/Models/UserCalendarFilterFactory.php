<?php

namespace Database\Factories\Artwork\Modules\UserCalendarFilter\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserCalendarFilterFactory extends Factory
{
    protected $model = UserCalendarFilter::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'is_loud' => false,
            'is_not_loud' => false,
            'adjoining_not_loud' => false,
            'has_audience' => false,
            'has_no_audience' => false,
            'adjoining_no_audience' => false,
            'show_free_rooms' => false,
            'show_adjoining_rooms' => false,
            'all_day_free' => false,
            'event_types' => [],
            'rooms' => [],
            'areas' => [],
            'room_attributes' => [],
            'room_categories' => [],
        ];
    }
}
