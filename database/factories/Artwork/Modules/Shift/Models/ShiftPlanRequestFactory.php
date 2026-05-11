<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftPlanRequest>
 */
class ShiftPlanRequestFactory extends Factory
{
    protected $model = ShiftPlanRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'craft_id' => Craft::factory(),
            'week_number' => $this->faker->numberBetween(1, 52),
            'year' => 2024,
            'status' => 'pending',
            'requested_by_user_id' => User::factory(),
        ];
    }
}
