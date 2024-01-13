<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use App\Models\Event;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'start' => today(),
            'end' => today()->addDay(),
            'craft_id' => Craft::factory(),
        ];
    }
}
