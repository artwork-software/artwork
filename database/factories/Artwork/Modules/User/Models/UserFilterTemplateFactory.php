<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilterTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserFilterTemplate>
 */
class UserFilterTemplateFactory extends Factory
{
    protected $model = UserFilterTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'user_id' => User::factory(),
        ];
    }
}
