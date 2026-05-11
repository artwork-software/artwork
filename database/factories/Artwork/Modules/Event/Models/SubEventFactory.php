<?php

namespace Database\Factories\Artwork\Modules\Event\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SubEvent;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubEvent>
 */
class SubEventFactory extends Factory
{
    protected $model = SubEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfHour();

        return [
            'event_id' => Event::factory(),
            'eventName' => $this->faker->text(20),
            'description' => $this->faker->sentence(),
            'start_time' => $start->toDateTimeString(),
            'end_time' => $start->copy()->addHours(2)->toDateTimeString(),
            'audience' => false,
            'is_loud' => false,
            'allDay' => false,
            'event_type_id' => EventType::factory(),
            'user_id' => User::factory(),
        ];
    }
}
