<?php

namespace Database\Factories\Artwork\Modules\User\Models;

use Artwork\Modules\User\Models\UserContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Alias-Factory, damit Laravel die Factory nach Standard-Konventionen
 * (basierend auf dem Model-Namespace) auflösen kann.
 *
 * @extends Factory<UserContract>
 */
class UserContractFactory extends Factory
{
    protected $model = UserContract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'free_full_days_per_week' => $this->faker->numberBetween(1, 2),
            'free_half_days_per_week' => $this->faker->numberBetween(0, 2),
            'special_day_rule_active' => $this->faker->boolean(),
            'compensation_period' => $this->faker->numberBetween(1, 12),
            'description' => $this->faker->optional()->sentence(),
            'free_sundays_per_season' => $this->faker->numberBetween(0, 10),
            'days_off_first_26_weeks' => $this->faker->numberBetween(0, 30),
        ];
    }
}
