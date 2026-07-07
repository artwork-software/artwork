<?php

namespace Database\Factories\Artwork\Modules\Budget\Models;

use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubPosition>
 */
class SubPositionFactory extends Factory
{
    protected $model = SubPosition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'main_position_id' => MainPosition::factory(),
            'name' => $this->faker->words(2, true),
            'position' => 0,
            'is_verified' => 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED',
            'is_fixed' => false,
        ];
    }
}
