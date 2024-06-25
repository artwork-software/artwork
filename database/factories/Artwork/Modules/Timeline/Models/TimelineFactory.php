<?php

namespace Database\Factories\Artwork\Modules\Timeline\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Timeline\Models\Timeline;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimelineFactory extends Factory
{
    protected $model = Timeline::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'start_date' => today(),
            'end_date' => today(),
            'start' => today()->addDay(),
            'end' => today()->addDay(),
            'description' => $this->faker->text(),
        ];
    }
}
