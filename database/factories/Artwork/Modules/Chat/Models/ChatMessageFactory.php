<?php

namespace Database\Factories\Artwork\Modules\Chat\Models;

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatMessage;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => Chat::factory(),
            'sender_id' => User::factory(),
            'message' => fake()->sentence(),
        ];
    }
}
