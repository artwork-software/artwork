<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftGroup>
 */
class ShiftGroupFactory extends Factory
{
    protected $model = ShiftGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'color' => $this->faker->hexColor(),
            'icon' => 'IconUsers',
        ];
    }
}
