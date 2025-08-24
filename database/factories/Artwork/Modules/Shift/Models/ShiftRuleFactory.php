<?php

namespace Database\Factories\Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\Models\ShiftRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftRuleFactory extends Factory
{
    protected $model = ShiftRule::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'trigger_type' => $this->faker->randomElement([
                'maxWorkingHoursOnDay',
                'maxConsecWorkingDays', 
                'weeklyMaxHours',
                'restTimeBeforeWorkday',
                'restTimeBeforeHoliday'
            ]),
            'individual_number_value' => $this->faker->randomFloat(1, 1, 24),
            'warning_color' => $this->faker->randomElement(['#ff0000', '#ff6b6b', '#ffa500', '#ffff00']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function maxWorkingHours(float $hours = 8.0): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => $hours,
        ]);
    }

    public function maxConsecutiveDays(int $days = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'maxConsecWorkingDays',
            'individual_number_value' => $days,
        ]);
    }
}