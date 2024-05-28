<?php

namespace Database\Factories\Artwork\Modules\UserCalendarAbo\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarAbo\Models\UserCalenderAbo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserCalenderAboFactory extends Factory
{
    protected $model = UserCalenderAbo::class;

    /**
     * @return string []
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'calendar_abo_id' => Str::uuid(),
        ];
    }
}
