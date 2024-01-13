<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TimeLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeLineFactory extends Factory
{
    protected $model = TimeLine::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'start' => today(),
            'end' => today()->addDay(),
            'description' => $this->faker->text(),
        ];
    }
}
