<?php

namespace Database\Factories\Artwork\Modules\UserShiftCalendarAbo\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserShiftCalendarAbo\Models\UserShiftCalendarAbo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserShiftCalendarAboFactory extends Factory
{
    protected $model = UserShiftCalendarAbo::class;

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
