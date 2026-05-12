<?php

namespace Database\Factories\Artwork\Modules\Event\Models;

use Artwork\Modules\Event\Models\SeriesEvents;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeriesEvents>
 */
class SeriesEventsFactory extends Factory
{
    protected $model = SeriesEvents::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'frequency_id' => 1,
            'end_date' => now()->addMonth()->toDateString(),
        ];
    }
}
