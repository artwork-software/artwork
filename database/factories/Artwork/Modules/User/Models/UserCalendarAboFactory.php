<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarAbo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserCalendarAbo>
 */
class UserCalendarAboFactory extends Factory
{
    protected $model = UserCalendarAbo::class;

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
