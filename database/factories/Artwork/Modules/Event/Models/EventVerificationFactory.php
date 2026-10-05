<?php

namespace Database\Factories\Artwork\Modules\Event\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventVerification>
 */
class EventVerificationFactory extends Factory
{
    protected $model = EventVerification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'verifier_type' => (new User())->getMorphClass(),
            'verifier_id' => User::factory(),
            'event_id' => Event::factory(),
            'request_user_id' => User::factory(),
        ];
    }
}
