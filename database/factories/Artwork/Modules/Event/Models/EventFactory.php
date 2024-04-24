<?php

namespace Database\Factories\Artwork\Modules\Event\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = now()->startOfMonth()->addHours($this->faker->numberBetween(1, 24 * 30));

        return [
            'name' => $this->faker->text(20),
            'eventName' => $this->faker->text(20),
            'description' => $this->faker->text,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $startTime->addHours($this->faker->numberBetween(1, 8))->toDateTimeString(),
            'occupancy_option' => $this->faker->boolean(0),
            'audience' => $this->faker->boolean(0),
            'is_loud' => $this->faker->boolean(0),
            'event_type_id' => EventType::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
        ];
    }
}
