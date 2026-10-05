<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShiftCalendarAbo>
 */
class UserShiftCalendarAboFactory extends Factory
{
    protected $model = UserShiftCalendarAbo::class;

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
