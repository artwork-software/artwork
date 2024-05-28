<?php

namespace Database\Factories\Artwork\Modules\UserCalendarAbo\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarAbo\Models\UserCalendarAbo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserCalendarAboFactory extends Factory
{
    protected $model = UserCalendarAbo::class;

    /**
     * @return string []
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
