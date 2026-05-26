<?php

namespace Database\Factories\Artwork\Modules\Event\Models;

use Artwork\Modules\Event\Models\EventProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventProperty>
 */
class EventPropertyFactory extends Factory
{
    protected $model = EventProperty::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'icon' => 'IconStar',
        ];
    }
}
