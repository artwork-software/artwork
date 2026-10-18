<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserWorkTime>
 */
class UserWorkTimeFactory extends Factory
{
    protected $model = UserWorkTime::class;

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
