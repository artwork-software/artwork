<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserFilter>
 */
class UserFilterFactory extends Factory
{
    protected $model = UserFilter::class;

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
