<?php

namespace Database\Factories\Artwork\Modules\Event\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventComment;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventComment>
 */
class EventCommentFactory extends Factory
{
    protected $model = EventComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'comment' => $this->faker->sentence(),
            'is_admin_comment' => false,
        ];
    }
}
