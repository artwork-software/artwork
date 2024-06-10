<?php

namespace Database\Factories\Artwork\Modules\DayService\Models;

use Artwork\Modules\DayService\Models\DayService;
use Illuminate\Database\Eloquent\Factories\Factory;

class DayServiceFactory extends Factory
{
    protected $model = DayService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'icon' => 'IconSpeakerphone',
            'hex_color' => '#000000',
        ];
    }
}
