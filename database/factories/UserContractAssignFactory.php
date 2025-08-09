<?php

namespace Database\Factories;

use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Artwork\Modules\User\Models\UserContractAssign>
 */
class UserContractAssignFactory extends Factory
{
    protected $model = UserContractAssign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \Artwork\Modules\User\Models\User::factory(),
            'user_contract_id' => \Database\Factories\UserContractFactory::new(),
            'free_full_days_per_week' => $this->faker->numberBetween(1, 2),
            'free_half_days_per_week' => $this->faker->numberBetween(0, 2),
            'special_day_rule_active' => $this->faker->boolean(),
            'compensation_period' => $this->faker->numberBetween(1, 12),
            'free_sundays_per_season' => $this->faker->numberBetween(0, 10),
            'days_off_first_26_weeks' => $this->faker->numberBetween(0, 30),
        ];
    }
}
