<?php

namespace Database\Factories\Artwork\Modules\Chat\Models;

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chat>
 */
class ChatFactory extends Factory
{
    protected $model = Chat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
        ];
    }
}
